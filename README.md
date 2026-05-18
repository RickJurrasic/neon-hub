<div align="center">

  <h1>🌌 Neon Social Hub</h1>
  <h3>Full-Stack Inertia.js SPA Demonstration</h3>

  <p>
    <img src="https://img.shields.io/badge/Status-In--Development-orange?style=for-the-badge&logo=laravel" alt="Status">
    <img src="https://img.shields.io/badge/Stack-Laravel_13_%2B_Vue_3-6366f1?style=for-the-badge&logo=vue.js" alt="Tech Stack">
  </p>

  <p>
    <img src="preview.png" alt="Neon Social Hub - Main Feed Interface" width="100%" style="border-radius: 6px; border: 1px solid #1e1e2f; margin-bottom: 10px;">
  </p>

  <p>
    <img src="preview2.png" alt="Neon Social Hub - Neon Network Entities" width="49%" style="border-radius: 6px; border: 1px solid #1e1e2f; margin-right: 1%;">
    <img src="preview3.png" alt="Neon Social Hub - Entity Profile Panel" width="49%" style="border-radius: 6px; border: 1px solid #1e1e2f;">
  </p>

  <p style="max-width: 800px; margin-top: 15px;">
    <strong>Neon Social Hub</strong> is a high-performance, responsive Single Page Application (SPA) showcasing a sophisticated cybernetic interface. The frontend layout, modular Vue components, and full responsiveness are complete, serving as the foundation for an upcoming asynchronous real-time event simulation layer.
  </p>

</div>

<hr>

<h2>🏗️ Architectural Blueprint</h2>

<p>The project is built on a monolithic SPA architecture, aiming to bridge a robust PHP backend with a highly reactive frontend using Inertia.js, avoiding the need for a decoupled REST API.</p>

<ul>
  <li><strong>Core Engine:</strong> Laravel 13 (PHP 8.5+) — <em>Basic boilerplate & routing established</em></li>
  <li><strong>Reactive Bridge:</strong> Inertia.js (SSR Ready)</li>
  <li><strong>Client Interface:</strong> Vue 3 (Composition API) + Vite 7 — <em>Core UI fully built</em></li>
  <li><strong>Visual Framework:</strong> Tailwind CSS 4 (High-performance hardware-accelerated layouts)</li>
  <li><strong>Real-time Layer (Planned):</strong> Laravel Reverb (Native WebSockets)</li>
</ul>

<hr>

<h2>🎭 The Frontend Core Modules</h2>

<p>The entire client interface is fully implemented with high fidelity and fluid responsiveness. The following active Vue components are ready to be wired into backend services:</p>

<h3>1. 📡 Network Live Feed (<code>NeonSocialFeed.vue</code> & <code>NeonSocialPost.vue</code>)</h3>
<p>The primary timeline interface designed to render streamed system data and communication packets. Currently populated with high-fidelity frontend mock streams, optimized and prepared for WebSocket integration inside <code>NeonTechDashboard.vue</code>.</p>

<h3>2. 🤝 Network Entities Terminal (<code>NeonFriends.vue</code>)</h3>
<p>An overlay module displaying active network uplink signals (Friend Requests). The UI container layout, text styling, and layout metrics are fully operational and ready to accept live Eloquent database records.</p>

<h3>3. 🧠 Entity Profile Diagnostic (<code>NeonUserProfile.vue</code>)</h3>
<p>A comprehensive user profile diagnostic sub-panel. Displays system authorization tags, network role classifications (e.g., <code>ARCHITECT_CORE</code>), trust metrics, and responsive modal positioning.</p>

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
  <li>✔️ High-fidelity UI/UX with 100% fluid responsiveness (Mobile/Tablet/Desktop layouts).</li>
  <li>✔️ Complete frontend component architecture and mock state integration.</li>
  <li>⏳ Wiring up <strong>Laravel Reverb</strong> for real-time WebSocket event broadcasting (<em>Current Milestone</em>).</li>
  <li>⬜ Developing asynchronous background Jobs & database models to drive the automated data streams.</li>
  <li>⬜ Full end-to-end integration and secure production deployment.</li>
</ul>
