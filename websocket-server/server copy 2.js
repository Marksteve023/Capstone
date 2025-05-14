// WebSocket + Serial Server for RFID Assignments and Attendance
const WebSocket = require('ws');
const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');

// WebSocket Server Setup
const wss = new WebSocket.Server({ port: 9000 });
console.log('✅ WebSocket Server running on ws://localhost:9000');

// Serial Port Setup (update COM17 to match your port)
const port = new SerialPort({ path: 'COM17', baudRate: 9600 });
const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

// Track mode per connected client ('attendance' or 'assign')
const clientModes = new Map();

// Serial Port: On RFID scan
parser.on('data', (rfidTag) => {
    console.log('📡 RFID Scanned from Serial:', rfidTag);

    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            const mode = clientModes.get(client);
            if (!mode) {
                console.warn('⚠️ Client has no mode set. Skipping RFID broadcast.');
                return;
            }
            const type = mode === 'assign' ? 'assign_rfid' : 'attendance';
            client.send(JSON.stringify({ type, rfid: rfidTag }));
        }
    });
});

// WebSocket Connections
wss.on('connection', ws => {
    console.log('🔌 A client connected');
    // ❌ Removed default mode assignment here

    ws.on('message', message => {
        try {
            const data = JSON.parse(message);

            // Set client mode
            if (data.type === 'set_mode') {
                clientModes.set(ws, data.mode);
                console.log(`🛠️ Client mode set to: ${data.mode}`);
                return;
            }

            // Manual RFID handling (optional fallback)
            if (data.type === 'assign_rfid' || data.type === 'attendance') {
                console.log(`📥 ${data.type} from client:`, data.rfid);
                wss.clients.forEach(client => {
                    if (client.readyState === WebSocket.OPEN) {
                        client.send(JSON.stringify({ type: data.type, rfid: data.rfid }));
                    }
                });
            }
        } catch (err) {
            console.error('❌ Error parsing WebSocket message:', err);
        }
    });

    ws.on('close', () => {
        console.log('🔌 Client disconnected');
        clientModes.delete(ws);
    });
});

// Serial port events
port.on('open', () => console.log('🟢 Serial port opened'));
port.on('error', err => console.error('❌ Serial port error:', err));
