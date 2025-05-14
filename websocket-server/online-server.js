// server.js (hosted on online server)
const WebSocket = require('ws');

const wss = new WebSocket.Server({ port: 9000 });
console.log('✅ WebSocket Server running on ws://your-domain.com:9000');

const clientModes = new Map();

wss.on('connection', ws => {
    console.log('🔌 A client connected');
    clientModes.set(ws, 'attendance');

    ws.on('message', message => {
        try {
            const data = JSON.parse(message);

            if (data.type === 'set_mode') {
                clientModes.set(ws, data.mode);
                console.log(`🛠️ Client mode set to: ${data.mode}`);
                return;
            }

            if (data.type === 'assign_rfid' || data.type === 'attendance') {
                console.log(`📥 ${data.type} from bridge:`, data.rfid);
                wss.clients.forEach(client => {
                    if (client.readyState === WebSocket.OPEN) {
                        const mode = clientModes.get(client);
                        if ((data.type === 'assign_rfid' && mode === 'assign') ||
                            (data.type === 'attendance' && mode === 'attendance')) {
                            client.send(JSON.stringify({ type: data.type, rfid: data.rfid }));
                        }
                    }
                });
            }
        } catch (err) {
            console.error('❌ Error parsing message:', err);
        }
    });

    ws.on('close', () => {
        console.log('🔌 Client disconnected');
        clientModes.delete(ws);
    });
});
