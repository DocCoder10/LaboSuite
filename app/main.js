const { app, BrowserWindow, dialog, shell } = require('electron');
const { spawn } = require('node:child_process');
const fs = require('node:fs');
const net = require('node:net');
const path = require('node:path');

let mainWindow;
let backendProcess;
let backendPort;

const BACKEND_HOST = '127.0.0.1';

if (process.platform === 'linux') {
    // Wayland/Vulkan can prevent window rendering on some distros.
    app.disableHardwareAcceleration();
    app.commandLine.appendSwitch('disable-gpu');
}

function getBackendPath() {
    return app.isPackaged
        ? path.join(process.resourcesPath, 'backend')
        : path.resolve(__dirname, '..', 'backend');
}

function getRuntimeDataPaths(backendPath) {
    if (!app.isPackaged) {
        return {
            databasePath: path.join(backendPath, 'database', 'database.sqlite'),
            storagePath: path.join(backendPath, 'storage'),
        };
    }

    const dataRoot = path.join(app.getPath('userData'), 'laravel');

    return {
        databasePath: path.join(dataRoot, 'database', 'database.sqlite'),
        storagePath: path.join(dataRoot, 'storage'),
    };
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

function ensureRuntimePaths(runtimePaths) {
    const databaseExists = fs.existsSync(runtimePaths.databasePath);
    const databaseSize = databaseExists ? fs.statSync(runtimePaths.databasePath).size : 0;

    fs.mkdirSync(path.dirname(runtimePaths.databasePath), { recursive: true });

    if (!databaseExists) {
        fs.writeFileSync(runtimePaths.databasePath, '', { encoding: 'utf8' });
    }

    const requiredStorageDirs = [
        path.join(runtimePaths.storagePath, 'app'),
        path.join(runtimePaths.storagePath, 'framework', 'cache', 'data'),
        path.join(runtimePaths.storagePath, 'framework', 'sessions'),
        path.join(runtimePaths.storagePath, 'framework', 'views'),
        path.join(runtimePaths.storagePath, 'logs'),
    ];

    requiredStorageDirs.forEach((dirPath) => {
        fs.mkdirSync(dirPath, { recursive: true });
    });

    return {
        shouldBootstrapSeed: !databaseExists || databaseSize === 0,
    };
}

function buildBackendEnv(runtimePaths) {
    const env = {
        ...process.env,
        APP_ENV: app.isPackaged ? 'production' : process.env.APP_ENV ?? 'local',
        APP_DEBUG: app.isPackaged ? 'false' : process.env.APP_DEBUG ?? 'true',
        DB_CONNECTION: 'sqlite',
        DB_DATABASE: runtimePaths.databasePath,
        LARAVEL_STORAGE_PATH: runtimePaths.storagePath,
    };

    if (app.isPackaged) {
        env.SESSION_DRIVER = 'file';
        env.CACHE_STORE = 'file';
        env.QUEUE_CONNECTION = 'sync';
        env.LOG_CHANNEL = 'daily';
    }

    return env;
}

async function runArtisan(phpBin, backendPath, args, backendEnv) {
    return new Promise((resolve, reject) => {
        const processRef = spawn(phpBin, ['artisan', ...args], {
            cwd: backendPath,
            stdio: 'pipe',
            env: backendEnv,
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
    const runtimePaths = getRuntimeDataPaths(backendPath);

    const runtimeState = ensureRuntimePaths(runtimePaths);
    const backendEnv = buildBackendEnv(runtimePaths);

    if (app.isPackaged && !fs.existsSync(phpBin)) {
        throw new Error(`Embedded PHP runtime not found at ${phpBin}.`);
    }

    backendPort = await findFreePort();
    const appUrl = `http://${BACKEND_HOST}:${backendPort}`;
    backendEnv.APP_URL = appUrl;

    // Prevent stale compiled views/routes/config from previous app sessions.
    await runArtisan(phpBin, backendPath, ['optimize:clear'], backendEnv);
    await runArtisan(phpBin, backendPath, ['migrate', '--force'], backendEnv);

    // Seed only for a fresh runtime database to avoid overwriting user data.
    if (runtimeState.shouldBootstrapSeed) {
        await runArtisan(phpBin, backendPath, ['db:seed', '--force'], backendEnv);
    }

    backendProcess = spawn(
        phpBin,
        ['artisan', 'serve', `--host=${BACKEND_HOST}`, `--port=${backendPort}`],
        {
            cwd: backendPath,
            stdio: 'pipe',
            env: backendEnv,
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
        backgroundColor: '#f8fafc',
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

    let isShown = false;
    const revealWindow = () => {
        if (isShown || !mainWindow) {
            return;
        }

        isShown = true;
        mainWindow.show();
        mainWindow.focus();
    };

    // Normal path.
    mainWindow.once('ready-to-show', revealWindow);
    // Fallback if ready-to-show is never emitted on some GPU/WM stacks.
    mainWindow.webContents.once('did-finish-load', revealWindow);
    mainWindow.webContents.on('did-fail-load', (_event, code, description, validatedUrl) => {
        console.error(`Renderer failed loading (${code}) ${description} at ${validatedUrl}`);
        revealWindow();
    });

    const revealTimeout = setTimeout(revealWindow, 4000);
    mainWindow.once('closed', () => {
        clearTimeout(revealTimeout);
    });

    await mainWindow.loadURL(appUrl);
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
