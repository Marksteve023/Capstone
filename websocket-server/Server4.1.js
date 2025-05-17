const WebSocket = require('ws');
const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const mysql = require('mysql2/promise');

// MySQL connection pool
const db = mysql.createPool({
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'attendance_monitoring_db'
});

// WebSocket Server Setup
const wss = new WebSocket.Server({ port: 9000 });
console.log('✅ WebSocket Server running on ws://localhost:9000');

// Serial Port Setup
const port = new SerialPort({ path: 'COM17', baudRate: 9600 });
const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

// Track mode per connected client
const clientModes = new Map();  

// On RFID scan from Arduino
parser.on('data', async (rfidTag) => {
    console.log('📡 RFID Scanned:', rfidTag);

    for (const client of wss.clients) {
        if (client.readyState !== WebSocket.OPEN) continue;

        const mode = clientModes.get(client);
        if (!mode) {
            console.warn('⚠️ No mode set for client.');
            continue;
        }

        if (mode === 'assign') {
            try {
                const [rows] = await db.query('SELECT * FROM students WHERE rfid_tag = ?', [rfidTag]);

                if (rows.length > 0) {
                    console.log(`⚠️ RFID ${rfidTag} already assigned.`);
                    // Send error to web client
                    client.send(JSON.stringify({
                        type: 'rfid_exists',
                        message: 'RFID already assigned',
                        rfid: rfidTag
                    }));
                    // Notify Arduino
                    port.write('assigned_error\n');
                } else {
                    // Allow assignment
                    client.send(JSON.stringify({
                        type: 'assign_rfid',
                        rfid: rfidTag
                    }));
                }
            } catch (err) {
                console.error('❌ Database error:', err);
                client.send(JSON.stringify({
                    type: 'error',
                    message: 'Database error while checking RFID'
                }));
            }
        } else {
            // Default: attendance mode
            client.send(JSON.stringify({ type: 'attendance', rfid: rfidTag }));
        }
    }
});

// WebSocket Connection Handling
wss.on('connection', ws => {
    console.log('🔌 A client connected');

    // Default mode is attendance
    const defaultMode = 'attendance';
    clientModes.set(ws, defaultMode);
    port.write(`${defaultMode}\n`);
    console.log(`🛠️ Default mode set and sent: ${defaultMode}`);

    ws.on('message', message => {
        try {
            const data = JSON.parse(message);

            if (data.type === 'set_mode') {
                const newMode = data.mode === 'assign' ? 'assign' : 'attendance';
                clientModes.set(ws, newMode);
                port.write(`${newMode}\n`);
                console.log(`➡️ Set mode: ${newMode}`);
            }

            // Optional: Manual broadcast of RFID
            if (data.type === 'assign_rfid' || data.type === 'attendance') {
                wss.clients.forEach(client => {
                    if (client.readyState === WebSocket.OPEN) {
                        client.send(JSON.stringify({ type: data.type, rfid: data.rfid }));
                    }
                });
            }

        } catch (err) {
            console.error('❌ Invalid WebSocket message:', err);
        }
    });

    ws.on('close', () => {
        console.log('🔌 Client disconnected');
        clientModes.delete(ws);
    });
});

// Serial events
port.on('open', () => console.log('🟢 Serial port opened'));
port.on('error', err => console.error('❌ Serial port error:', err));
