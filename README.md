<div align="center">

  <h1>🌌 Neon Social Hub</h1>
  <h3>Full-Stack Inertia.js SPA Demonstration</h3>

  <p>
    <img src="https://img.shields.io/badge/Status-In--Development-orange?style=for-the-badge&logo=laravel" alt="Status">
    <img src="https://img.shields.io/badge/Stack-Laravel_12_%2B_Vue_3-6366f1?style=for-the-badge&logo=vue.js" alt="Tech Stack">
  </p>

  <p>
    <img src="preview.png" alt="Neon Social Hub Dashboard Preview" width="800" style="border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.5);">
  </p>

  <p>
    <strong>Neon Social Hub</strong> is a high-performance, responsive Single Page Application (SPA) showcasing a sophisticated cybernetic interface. It simulates a live social operating system driven by autonomous AI agents running asynchronously via a Laravel backend.
  </p>

</div>

<hr>

<h2>🏗️ Architectural Overview</h2>

<p>The project demonstrates a seamless monolithic SPA architecture, bridging the gap between a robust PHP backend and a highly reactive frontend without the complexity of a decoupled REST API.</p>

<ul>
  <li><strong>Core Engine:</strong> Laravel 12 (PHP 8.2+)</li>
  <li><strong>Reactive Bridge:</strong> Inertia.js (SSR Enabled)</li>
  <li><strong>Client Interface:</strong> Vue 3 (Composition API) + Vite 7</li>
  <li><strong>Real-time Layer:</strong> Laravel Reverb (Native WebSockets)</li>
  <li><strong>Visual Framework:</strong> Tailwind CSS 4 (High-performance hardware-accelerated UI)</li>
</ul>

<hr>

<h2>🎭 The "AI Theatre" Concept</h2>

<p>Unlike traditional static dashboards, <strong>Neon Social Hub</strong> simulates an active network environment:</p>

<ol>
  <li><strong>Handshake:</strong> The <code>NeonGate.vue</code> and <code>NeonOverlay.vue</code> handle initial authentication and system entry.</li>
  <li><strong>Asynchronous Timeline:</strong> Triggering the <em>ENTER SYSTEM</em> command dispatches an <code>InitializeAiTheatreJob</code> to the background queue.</li>
  <li><strong>Live Broadcasting:</strong> Background AI agents (e.g., <em>BOT_EPSILON</em>) generate real-time events. These events are broadcasted via <strong>Laravel Reverb</strong> and captured instantly by <strong>Laravel Echo</strong> on the client side.</li>
</ol>

<hr>

<h2>📁 Key System Components</h2>

<p>Designed with modularity and 60 FPS performance in mind:</p>

<ul>
  <li><code>NeonTechDashboard.vue</code>: Central command & real-time state coordinator.</li>
  <li><code>NeonGate.vue</code>: Mechanical animation logic for system entry.</li>
  <li><code>ObserverLogs.vue</code>: Live system log stream broadcasted from the server.</li>
  <li><code>AlertCenter.vue</code>: Critical state management and bot action handling.</li>
</ul>

<hr>

<h2>🛠️ Installation & Daemon Setup</h2>

<ol>
  <li>
    <strong>Clone & Dependencies:</strong>
    <pre><code>git clone https://github.com/RickJurrasic/neon-hub
composer install && npm install</code></pre>
  </li>
  <li>
    <strong>Environment:</strong>
    <pre><code>cp .env.example .env && php artisan key:generate</code></pre>
  </li>
  <li>
    <strong>Database:</strong>
    <pre><code>php artisan migrate --seed</code></pre>
  </li>
  <li>
    <strong>Execution (Requires 3 active terminals):</strong>
    <ul>
      <li><strong>App & Frontend:</strong> <code>php artisan serve</code> & <code>npm run dev</code></li>
      <li><strong>WebSockets:</strong> <code>php artisan reverb:start</code></li>
      <li><strong>Queues:</strong> <code>php artisan queue:work</code></li>
    </ul>
  </li>
</ol>

<hr>

<h2>🗺️ Roadmap</h2>

<ul>
  <li>✔️ High-fidelity UI/UX with 100% responsiveness (Mobile/Tablet/Desktop).</li>
  <li>✔️ Reactive state architecture for local mock data.</li>
  <li>⏳ Integration of <strong>Laravel Reverb</strong> real-time broadcasting layer (<em>Current Milestone</em>).</li>
  <li>⬜ Backend AI Logic: Autonomous Jobs linked to Eloquent models.</li>
  <li>⬜ Production Deployment with WSS (Secure WebSockets) support.</li>
</ul>
