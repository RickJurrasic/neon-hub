<div align="center">

  <h1>🌌 Neon Social Hub</h1>
  <h3>Full-Stack Inertia.js SPA Demonstration</h3>

  <p>
    <img src="https://img.shields.io/badge/Status-In--Development-orange?style=for-the-badge&logo=laravel" alt="Status">
    <img src="https://img.shields.io/badge/Stack-Laravel_13_%2B_Vue_3-6366f1?style=for-the-badge&logo=vue.js" alt="Tech Stack">
  </p>

  <p>
    <img src="preview.png" alt="Neon Social Hub - Main Interface" width="100%" style="border-radius: 6px; border: 1px solid #1e1e2f; margin-bottom: 10px;">
  </p>

  <p>
    <img src="preview2.png" alt="Neon Social Hub - Neon Network Entities" width="49%" style="border-radius: 6px; border: 1px solid #1e1e2f; margin-right: 1%;">
    <img src="preview3.png" alt="Neon Social Hub - Entity Profile Diagnostic" width="49%" style="border-radius: 6px; border: 1px solid #1e1e2f;">
  </p>

  <p style="max-width: 800px; margin-top: 15px;">
    <strong>Neon Social Hub</strong> is a high-performance, responsive Single Page Application (SPA) showcasing a sophisticated cybernetic user interface. The entire frontend architecture, complex layout components, hardware-accelerated animations, and responsive breakpoints are fully completed, serving as a robust shell for an upcoming asynchronous backend layer.
  </p>

</div>

<hr>

<h2>🏗️ Architectural Blueprint</h2>

<p>The application leverages a modern monolithic SPA architecture using Inertia.js to eliminate the overhead of a decoupled REST API, ensuring a seamless bridge between a bleeding-edge PHP runtime and a reactive user interface.</p>

<ul>
  <li><strong>Core Engine:</strong> Laravel 13 (PHP 8.5+) — <em>Boilerplate framework structure and default web routing established</em></li>
  <li><strong>Reactive Bridge:</strong> Inertia.js (SSR Ready)</li>
  <li><strong>Client Interface:</strong> Vue 3 (Composition API) + Vite 7 — <em>Core UI fully built</em></li>
  <li><strong>Visual Framework:</strong> Tailwind CSS 4 (High-performance hardware-accelerated layouts)</li>
  <li><strong>Iconography:</strong> Lucide Vue Next</li>
</ul>

<hr>

<h2>🎭 Fully Implemented Frontend Core Modules</h2>

<p>The system's client layer is fully modeled with high visual fidelity, sci-fi styling indicators, and isolated scopes. The following specialized Vue components are completed and ready for data serialization:</p>

<h3>1. 🔒 System Entry Portal (<code>NeonGate.vue</code> & <code>NeonOverlay.vue</code>)</h3>
<p>A dual-wing gateway structure running synchronous 1800ms hardware-accelerated transforms (<code>translate3d</code>). Features a <code>conic-gradient</code> rotating border mechanism displaying the high-level system manifesto and restricting user input until authorization occurs.</p>

<h3>2. 📡 Telemetry Dashboard Navigation (<code>NeonNav.vue</code>)</h3>
<p>A persistent, high-density dashboard matrix tracking mock infrastructure parameters in real-time. Displays simulated connection metrics (Network Latency: 12ms, System Uptime: 99.98%, Active Nodes: 1,204), administrative tags (<code>Admin_Root // 0x882_ALPHA</code>), and animated visual hardware status indicators.</p>

<h3>3. 🤝 Network Entities Terminal (<code>NeonFriends.vue</code>)</h3>
<p>A high-capacity, scroll-contained directory displaying active encryption endpoints (Friend Matrix). Built using custom lightweight scrollbar definitions and standard flex structures, prepared to ingest live Eloquent database collections.</p>

<h3>4. 💬 Comms Stream Sub-System (<code>NeonCommentSection.vue</code>)</h3>
<p>A reactive discussion interface utilizing strict event-based emit signatures (<code>send-comment</code>) to communicate with parent scopes. Features deep style definitions for scroll containers, localized data structures tracking target metadata (<code>author</code>, <code>text</code>, <code>timestamp</code>), and layout metrics optimized for messaging.</p>

<h3>5. 📥 Signal Inbox Decryptor (<code>NeonMessages.vue</code>)</h3>
<p>An isolated message preview workspace designed to visualize backend communication packets, populated with clean template configurations and standard event hooks for modular termination.</p>

<hr>

<h2>🛠️ Installation & Setup</h2>

<ol>
  <li>
    <strong>Clone the repository & install dependencies:</strong>
    <pre><code>git clone https://github.com/RickJurrasic/neon-hub
composer install && npm install</code></pre>
  </li>
  <li>
    <strong>Configure environment variables & initialize application key:</strong>
    <pre><code>cp .env.example .env && php artisan key:generate</code></pre>
  </li>
  <li>
    <strong>Compile assets and start the local development server:</strong>
    <pre><code>npm run dev
php artisan serve</code></pre>
  </li>
</ol>

<hr>

<h2>🗺️ Project Roadmap</h2>

<ul>
  <li>✔️ High-fidelity sci-fi UI/UX with 100% fluid responsiveness across all target screens.</li>
  <li>✔️ Complete component parsing architecture with isolated style sheets and mock data models.</li>
  <li>⏳ Integrating <strong>Laravel Reverb</strong> for native asynchronous WebSocket event broadcasting (<em>Current Milestone</em>).</li>
  <li>⬜ Designing database migrations and Eloquent relationships to drive active entity pipelines.</li>
  <li>⬜ Full deployment audit and production server integration.</li>
</ul>
