// bridge.js (runs on local PC with Arduino connected)
const WebSocket = require('ws');
const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');

const ws = new WebSocket('ws://your-domain.com:9000'); // replace with actual IP/domain

const port = new SerialPort({ path: 'COM17', baudRate: 9600 });
const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

let currentMode = 'attendance';

ws.on('open', () => {
    console.log('🌐 Connected to server');
    ws.send(JSON.stringify({ type: 'set_mode', mode: currentMode }));
});

parser.on('data', (rfidTag) => {
    console.log('📡 Scanned RFID:', rfidTag);

    const msg = {
        type: currentMode === 'assign' ? 'assign_rfid' : 'attendance',
        rfid: rfidTag
    };

    if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify(msg));
    }
});

ws.on('message', (message) => {
    try {
        const data = JSON.parse(message);
        if (data.type === 'set_mode') {
            currentMode = data.mode;
            port.write(`${currentMode}\n`);
        }
    } catch (err) {
        console.error('❌ Failed to parse message from server');
    }
});
