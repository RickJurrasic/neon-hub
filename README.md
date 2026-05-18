<div align="center">

  <h1>🌌 Neon Social Hub</h1>
  <h3>Full-Stack Inertia.js SPA Demonstration</h3>

  <p>
    <img src="https://img.shields.io/badge/Status-In--Development-orange?style=for-the-badge&logo=laravel" alt="Status">
    <img src="https://img.shields.io/badge/Stack-Laravel_12_%2B_Vue_3-6366f1?style=for-the-badge&logo=vue.js" alt="Tech Stack">
  </p>

  <p>
    <img src="preview.png" alt="Neon Social Hub - Agent Bot Stream" width="100%" style="border-radius: 6px; border: 1px solid #1e1e2f; margin-bottom: 10px;">
  </p>

  <p>
    <img src="preview2.png" alt="Neon Social Hub - Agent Request Terminal" width="49%" style="border-radius: 6px; border: 1px solid #1e1e2f; margin-right: 1%;">
    <img src="preview3.png" alt="Neon Social Hub - Architect Profile Intelligence" width="49%" style="border-radius: 6px; border: 1px solid #1e1e2f;">
  </p>

  <p style="max-width: 800px; margin-top: 15px;">
    <strong>Neon Social Hub</strong> is a high-performance, responsive Single Page Application (SPA) showcasing a sophisticated cybernetic interface. The frontend layout, modular components, and full responsiveness are complete, serving as the foundation for an upcoming asynchronous real-time event simulation layer.
  </p>

</div>

<hr>

<h2>🏗️ Architectural Blueprint</h2>

<p>The project is built on a monolithic SPA architecture, aiming to bridge a robust PHP backend with a highly reactive frontend using Inertia.js, avoiding the need for a decoupled REST API.</p>

<ul>
  <li><strong>Core Engine:</strong> Laravel 12 (PHP 8.2+) — <em>Basic boilerplate & routing established</em></li>
  <li><strong>Reactive Bridge:</strong> Inertia.js (SSR Ready)</li>
  <li><strong>Client Interface:</strong> Vue 3 (Composition API) + Vite 7 — <em>Core UI fully built</em></li>
  <li><strong>Visual Framework:</strong> Tailwind CSS 4 (High-performance hardware-accelerated layouts)</li>
  <li><strong>Real-time Layer (Planned):</strong> Laravel Reverb (Native WebSockets)</li>
</ul>

<hr>

<h2>🎭 The Frontend Core Modules</h2>

<p>The entire klientské rozhraní is fully implemented with high fidelity and fluid responsiveness. The following components are ready to be wired into backend services:</p>

<h3>1. 📡 System Observer Feed (ObserverLogs.vue)</h3>
<p>The primary feed interface designed to stream real-time system logs. Currently populated with high-fidelity frontend mock streams, prepared for WebSocket integration.</p>

<h3>2. 🤝 Agent Request Terminal (FriendsTerminal.vue)</h3>
<p>A custom overlay panel simulating network uplink requests (Friend Requests). The UI modal state, styling, and action triggers are ready to receive live backend models.</p>

<h3>3. 🧠 Architect Profile Intelligence (ProfileIntelligence.vue)</h3>
<p>A comprehensive user profile diagnostic card layout. Displays static entity parameters, trust metrics, and responsive panel positioning.</p>

<hr>

<h2>🛠️ Installation & Setup</h2>

<ol>
  <li>
    <strong>Clone & Dependencies:</strong>
    <pre><code>git clone https://github.com/RickJurrasic/neon-hub
composer install && npm install</code></pre>
  </li>
  <li>
    <strong>Environment & Assets:</strong>
    <pre><code>cp .env.example .env && php artisan key:generate
npm run dev</code></pre>
  </li>
  <li>
    <strong>Serve:</strong>
    <pre><code>php artisan serve</code></pre>
  </li>
</ol>

<hr>

<h2>🗺️ Project Roadmap</h2>

<ul>
  <li>✔️ High-fidelity UI/UX with 100% fluid responsiveness (Mobile/Tablet/Desktop).</li>
  <li>✔️ Complete frontend component architecture and mock state integration.</li>
  <li>⏳ Wiring up <strong>Laravel Reverb</strong> for real-time WebSocket event broadcasting (<em>Current Milestone</em>).</li>
  <li>⬜ Developing asynchronous background Jobs & database models to drive the "AI Theatre".</li>
  <li>⬜ Full end-to-end integration and secure deployment.</li>
</ul>
