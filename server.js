/**
 * =====================================================
 * BloomWell AI Mood Tracker - Backend Server
 * =====================================================
 * 
 * File: server.js
 * Purpose: Backend yang menangani komunikasi dengan Anthropic API
 *          API key disimpan aman di server, bukan di frontend
 * 
 * Installation:
 * $ npm install
 * 
 * Run:
 * $ npm start        (production)
 * $ npm run dev      (development dengan auto-reload)
 * 
 * =====================================================
 */

// ==========================================
// 1. IMPORTS & SETUP
// ==========================================
const express = require('express');
const cors = require('cors');
require('dotenv').config();
const { GoogleGenerativeAI } = require("@google/generative-ai");

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(express.json());

// ==========================================
// 2. INIT ANTHROPIC CLIENT
// ==========================================
const genAI = new GoogleGenerativeAI(process.env.GEMINI_API_KEY);

app.post('/api/analyze-mood', async (req, res) => {
  try {
    const { mood, note } = req.body;
    
    const model = genAI.getGenerativeModel({ model: "gemini-pro" });
    const result = await model.generateContent(`Mood: ${mood}, Note: ${note}`);
    
    res.json({ success: true, result: result.response.text() });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.listen(3000, () => console.log('Server running on port 3000'));

// ==========================================
// 3. STATS TRACKING
// ==========================================
const stats = {
  totalRequests: 0,
  successRequests: 0,
  failedRequests: 0,
  totalTokens: 0,
  startTime: new Date()
};

// ==========================================
// 4. HEALTH CHECK ENDPOINT
// ==========================================
/**
 * GET /health
 * Cek apakah server berjalan
 */
app.get('/health', (req, res) => {
  res.json({
    status: 'OK',
    message: 'BloomWell AI Mood Tracker Server is running',
    timestamp: new Date().toISOString()
  });
});

// ==========================================
// 5. MAIN ENDPOINT - ANALYZE MOOD
// ==========================================
/**
 * POST /api/analyze-mood
 * 
 * Request body:
 * {
 *   "note": "string (catatan harian)",
 *   "mood": "string (very-sad, sad, neutral, happy, very-happy)"
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "analysis": "string (AI analysis)",
 *   "timestamp": "ISO string",
 *   "usage": {
 *     "input_tokens": number,
 *     "output_tokens": number
 *   }
 * }
 */
app.post('/api/analyze-mood', async (req, res) => {
  try {
    stats.totalRequests++;

    // ===== VALIDATION =====
    const { note, mood } = req.body;

    if (!note || !note.trim()) {
      stats.failedRequests++;
      return res.status(400).json({
        success: false,
        error: 'Note tidak boleh kosong'
      });
    }

    if (!mood) {
      stats.failedRequests++;
      return res.status(400).json({
        success: false,
        error: 'Mood harus dipilih'
      });
    }

    // Validate mood value
    const validMoods = ['very-sad', 'sad', 'neutral', 'happy', 'very-happy'];
    if (!validMoods.includes(mood)) {
      stats.failedRequests++;
      return res.status(400).json({
        success: false,
        error: `Mood tidak valid. Pilih: ${validMoods.join(', ')}`
      });
    }

    // Limit note length (prevent abuse)
    if (note.length > 5000) {
      stats.failedRequests++;
      return res.status(400).json({
        success: false,
        error: 'Catatan terlalu panjang (max 5000 karakter)'
      });
    }

    console.log(`[${new Date().toISOString()}] Analyzing mood: ${mood} | Length: ${note.length}`);

    // ===== BUILD PROMPT =====
    const systemPrompt = `Anda adalah konselor kesehatan mental berpengalaman yang membantu menganalisis catatan harian emosional.
Berikan analisis yang empati, konstruktif, dan actionable.
Jawab dalam Bahasa Indonesia.`;

    const userPrompt = `Catatan pengguna:
"${note}"

Mood saat ini: ${mood}

Silakan berikan analisis LENGKAP dengan format berikut:

**ANALISIS KESELURUHAN:**
[Ringkas kondisi emosional dan faktor-faktor utama yang teridentifikasi]

**PREDIKSI MOOD MASA DEPAN (7 HARI KE DEPAN):**
[Prediksi bagaimana mood mereka kemungkinan akan berkembang berdasarkan pola yang teridentifikasi]

**POTENSI MASALAH:**
[Identifikasi masalah spesifik yang mungkin timbul jika pola saat ini berlanjut]

**SOLUSI & REKOMENDASI:**
- [Solusi 1: konkrit dan actionable]
- [Solusi 2]
- [Solusi 3]
- [Solusi 4]
- [Solusi 5]

Berikan rekomendasi yang praktis dan disesuaikan dengan situasi spesifik mereka.`;

    // ===== CALL CLAUDE API =====
    const message = await anthropic.messages.create({
      model: 'claude-opus-4-1-20250805',
      max_tokens: 1500,
      system: systemPrompt,
      messages: [
        {
          role: 'user',
          content: userPrompt
        }
      ]
    });

    // ===== PARSE RESPONSE =====
    const aiResponse = message.content[0].type === 'text' 
      ? message.content[0].text 
      : 'Error: Tidak bisa parse response dari Claude';

    // ===== UPDATE STATS =====
    stats.successRequests++;
    stats.totalTokens += message.usage.input_tokens + message.usage.output_tokens;

    // ===== SEND RESPONSE =====
    res.json({
      success: true,
      analysis: aiResponse,
      timestamp: new Date().toISOString(),
      usage: {
        input_tokens: message.usage.input_tokens,
        output_tokens: message.usage.output_tokens,
        total_tokens: message.usage.input_tokens + message.usage.output_tokens
      }
    });

  } catch (error) {
    stats.failedRequests++;
    console.error('[ERROR]', error.message);

    // Handle Anthropic-specific errors
    if (error.status === 401) {
      return res.status(401).json({
        success: false,
        error: 'API Key tidak valid atau sudah expired',
        code: 'INVALID_API_KEY'
      });
    }

    if (error.status === 429) {
      return res.status(429).json({
        success: false,
        error: 'Rate limit tercapai. Coba lagi dalam beberapa saat',
        code: 'RATE_LIMIT_EXCEEDED'
      });
    }

    if (error.status === 500) {
      return res.status(503).json({
        success: false,
        error: 'Server Anthropic sedang down. Coba lagi nanti',
        code: 'SERVICE_UNAVAILABLE'
      });
    }

    // Generic error
    res.status(500).json({
      success: false,
      error: error.message || 'Terjadi error saat menganalisis mood',
      code: 'INTERNAL_ERROR'
    });
  }
});

// ==========================================
// 6. ENDPOINT - SERVER STATS
// ==========================================
/**
 * GET /api/stats
 * Lihat statistik server
 */
app.get('/api/stats', (req, res) => {
  const uptime = new Date() - stats.startTime;
  const successRate = stats.totalRequests > 0 
    ? (stats.successRequests / stats.totalRequests * 100).toFixed(2)
    : 0;

  res.json({
    server: {
      uptime_ms: uptime,
      uptime_hours: (uptime / 1000 / 60 / 60).toFixed(2),
      uptime_readable: formatUptime(uptime),
      start_time: stats.startTime.toISOString()
    },
    requests: {
      total: stats.totalRequests,
      success: stats.successRequests,
      failed: stats.failedRequests,
      success_rate_percent: parseFloat(successRate)
    },
    tokens: {
      total_processed: stats.totalTokens,
      average_per_request: stats.totalRequests > 0 
        ? (stats.totalTokens / stats.totalRequests).toFixed(2)
        : 0
    }
  });
});

// ==========================================
// 7. ENDPOINT - API INFO
// ==========================================
/**
 * GET /api/info
 * Informasi API dan available endpoints
 */
app.get('/api/info', (req, res) => {
  res.json({
    name: 'BloomWell AI Mood Tracker API',
    version: '1.0.0',
    endpoints: [
      {
        method: 'GET',
        path: '/health',
        description: 'Health check'
      },
      {
        method: 'POST',
        path: '/api/analyze-mood',
        description: 'Analyze user mood note with AI',
        body: {
          note: 'string (catatan harian)',
          mood: 'string (very-sad|sad|neutral|happy|very-happy)'
        }
      },
      {
        method: 'GET',
        path: '/api/stats',
        description: 'Server statistics'
      },
      {
        method: 'GET',
        path: '/api/info',
        description: 'API information'
      }
    ]
  });
});

// ==========================================
// 8. ERROR HANDLING
// ==========================================

/**
 * 404 Handler
 */
app.use((req, res) => {
  res.status(404).json({
    success: false,
    error: `Endpoint tidak ditemukan: ${req.method} ${req.path}`,
    code: 'NOT_FOUND',
    hint: 'Lihat GET /api/info untuk list endpoint yang tersedia'
  });
});

/**
 * Error Middleware
 */
app.use((err, req, res, next) => {
  console.error('Unhandled error:', err);
  res.status(500).json({
    success: false,
    error: 'Internal server error',
    code: 'INTERNAL_ERROR'
  });
});

// ==========================================
// 9. HELPER FUNCTIONS
// ==========================================

function formatUptime(ms) {
  const seconds = Math.floor((ms / 1000) % 60);
  const minutes = Math.floor((ms / (1000 * 60)) % 60);
  const hours = Math.floor((ms / (1000 * 60 * 60)) % 24);
  const days = Math.floor(ms / (1000 * 60 * 60 * 24));

  if (days > 0) return `${days}d ${hours}h`;
  if (hours > 0) return `${hours}h ${minutes}m`;
  if (minutes > 0) return `${minutes}m ${seconds}s`;
  return `${seconds}s`;
}

// ==========================================
// 10. START SERVER
// ==========================================

const server = app.listen(PORT, () => {
  console.log(`
╔════════════════════════════════════════════════════╗
║  BloomWell AI Mood Tracker - Backend Server v1.0   ║
╚════════════════════════════════════════════════════╝

✅ Server is running!

📍 Endpoints:
   • http://localhost:${PORT}/health
   • http://localhost:${PORT}/api/analyze-mood (POST)
   • http://localhost:${PORT}/api/stats
   • http://localhost:${PORT}/api/info

🔐 Configuration:
   • NODE_ENV: ${process.env.NODE_ENV || 'development'}
   • PORT: ${PORT}
   • API Key: ${process.env.ANTHROPIC_API_KEY ? '✓ Configured' : '✗ MISSING (Set ANTHROPIC_API_KEY in .env)'}
   • CORS: ✓ Enabled

💡 Test dengan:
   curl -X POST http://localhost:${PORT}/api/analyze-mood \\
     -H "Content-Type: application/json" \\
     -d '{"mood":"happy","note":"Hari ini saya merasa senang"}'

📚 Dokumentasi: GET /api/info
  `);
});

// ==========================================
// 11. GRACEFUL SHUTDOWN
// ==========================================

function shutdown() {
  console.log('\n🛑 Shutting down server...');
  server.close(() => {
    console.log('✓ Server closed');
    process.exit(0);
  });
}

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);

module.exports = app;
