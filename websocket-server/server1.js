// WebSocket Server for RFID Assignments and Attendance
const WebSocket = require('ws');

// WebSocket Server Setup
const wss = new WebSocket.Server({ port: 9000 });
console.log('✅ WebSocket Server running on ws://localhost:9000');

// Track mode per connected client ('attendance' or 'assign')
const clientModes = new Map();

// WebSocket Connections
wss.on('connection', ws => {
    console.log('🔌 A client connected');
    clientModes.set(ws, 'attendance'); // Default mode

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
