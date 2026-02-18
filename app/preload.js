const { contextBridge } = require('electron');

contextBridge.exposeInMainWorld('lmsDesktop', {
    platform: process.platform,
    version: process.versions.electron,
});
