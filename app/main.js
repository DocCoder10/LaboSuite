const { app, BrowserWindow, dialog, shell } = require('electron');
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const net = require('node:net');
const path = require('node:path');

let mainWindow;
let backendProcess;
let backendPort;

const BACKEND_HOST = '127.0.0.1';

function getBackendPath() {
    return app.isPackaged
        ? path.join(process.resourcesPath, 'backend')
        : path.resolve(__dirname, '..', 'backend');
}

function resolvePhpBinary() {
    if (process.env.LMS_PHP_BIN) {
        return process.env.LMS_PHP_BIN;
    }

    if (!app.isPackaged) {
        return 'php';
    }

    if (process.platform === 'win32') {
        return path.join(process.resourcesPath, 'runtime', 'php', 'php.exe');
    }

    return path.join(process.resourcesPath, 'runtime', 'php', 'bin', 'php');
}

function checkPort(port) {
    return new Promise((resolve) => {
        const socket = new net.Socket();

        socket
            .once('connect', () => {
                socket.destroy();
                resolve(false);
            })
            .once('error', () => {
                resolve(true);
            })
            .connect(port, BACKEND_HOST);
    });
}

async function findFreePort(start = 17890, end = 17930) {
    for (let port = start; port <= end; port += 1) {
        const isFree = await checkPort(port);

        if (isFree) {
            return port;
        }
    }

    throw new Error('No available backend port found.');
}

function waitForHealth(url, timeoutMs = 25000) {
    const start = Date.now();

    return new Promise((resolve, reject) => {
        const poll = () => {
            fetch(`${url}/health`)
                .then((response) => {
                    if (response.ok) {
                        resolve();
                        return;
                    }

                    if (Date.now() - start >= timeoutMs) {
                        reject(new Error('Backend health check failed.'));
                        return;
                    }

                    setTimeout(poll, 500);
                })
                .catch(() => {
                    if (Date.now() - start >= timeoutMs) {
                        reject(new Error('Backend did not start in time.'));
                        return;
                    }

                    setTimeout(poll, 500);
                });
        };

        poll();
    });
}

function ensureDatabaseFile(backendPath) {
    const dbPath = path.join(backendPath, 'database', 'database.sqlite');

    if (!fs.existsSync(dbPath)) {
        fs.mkdirSync(path.dirname(dbPath), { recursive: true });
        fs.writeFileSync(dbPath, '', { encoding: 'utf8' });
    }
}

async function runArtisan(phpBin, backendPath, args) {
    return new Promise((resolve, reject) => {
        const processRef = spawn(phpBin, ['artisan', ...args], {
            cwd: backendPath,
            stdio: 'pipe',
            env: {
                ...process.env,
                APP_ENV: app.isPackaged ? 'production' : process.env.APP_ENV ?? 'local',
                APP_DEBUG: app.isPackaged ? 'false' : process.env.APP_DEBUG ?? 'true',
            },
        });

        let stderr = '';

        processRef.stderr.on('data', (chunk) => {
            stderr += chunk.toString();
        });

        processRef.on('error', reject);

        processRef.on('close', (code) => {
            if (code !== 0) {
                reject(new Error(`artisan ${args.join(' ')} failed: ${stderr}`));
                return;
            }

            resolve();
        });
    });
}

async function startBackend() {
    const backendPath = getBackendPath();
    const phpBin = resolvePhpBinary();

    ensureDatabaseFile(backendPath);

    if (app.isPackaged && !fs.existsSync(phpBin)) {
        throw new Error(`Embedded PHP runtime not found at ${phpBin}.`);
    }

    backendPort = await findFreePort();

    await runArtisan(phpBin, backendPath, ['migrate', '--force', '--seed']);

    backendProcess = spawn(
        phpBin,
        ['artisan', 'serve', `--host=${BACKEND_HOST}`, `--port=${backendPort}`],
        {
            cwd: backendPath,
            stdio: 'pipe',
            env: {
                ...process.env,
                APP_ENV: app.isPackaged ? 'production' : process.env.APP_ENV ?? 'local',
                APP_DEBUG: app.isPackaged ? 'false' : process.env.APP_DEBUG ?? 'true',
            },
        },
    );

    backendProcess.stdout.on('data', (chunk) => {
        process.stdout.write(`[backend] ${chunk}`);
    });

    backendProcess.stderr.on('data', (chunk) => {
        process.stderr.write(`[backend] ${chunk}`);
    });

    backendProcess.on('error', (error) => {
        console.error('Backend process error:', error);
    });

    const appUrl = `http://${BACKEND_HOST}:${backendPort}`;
    await waitForHealth(appUrl);

    return appUrl;
}

function stopBackend() {
    if (backendProcess && !backendProcess.killed) {
        backendProcess.kill();
    }

    backendProcess = undefined;
}

async function createWindow() {
    const appUrl = await startBackend();

    mainWindow = new BrowserWindow({
        width: 1480,
        height: 920,
        minWidth: 1200,
        minHeight: 760,
        show: false,
        autoHideMenuBar: true,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            contextIsolation: true,
            nodeIntegration: false,
            sandbox: true,
        },
    });

    mainWindow.webContents.setWindowOpenHandler(({ url }) => {
        shell.openExternal(url);
        return { action: 'deny' };
    });

    await mainWindow.loadURL(appUrl);
    mainWindow.once('ready-to-show', () => {
        mainWindow.show();
    });
}

app.whenReady()
    .then(createWindow)
    .catch(async (error) => {
        await dialog.showMessageBox({
            type: 'error',
            title: 'LaboSuite startup error',
            message: 'Unable to start the local backend service.',
            detail: String(error),
        });

        app.quit();
    });

app.on('window-all-closed', () => {
    stopBackend();

    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('before-quit', () => {
    stopBackend();
});
