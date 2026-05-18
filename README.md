🌌 Neon Cyber Hub (Inertia Full-Stack SPA)
Project Status: 🛠️ In Active Development (Currently integrating the real-time event infrastructure)

Neon Cyber Hub is an advanced, highly responsive Single Page Application (SPA) wrapped in a dark cyberpunk operational interface. The project serves as a flagship technical demonstration simulating a live cybernetic operating system driven by autonomous AI agents running asynchronously in the background.

The primary architectural goal of this project is to demonstrate a seamless, monolithic coupling of a robust PHP backend with a reactive client-side interface without the overhead of building and maintaining a decoupled REST API, fully leveraging the modern Laravel ecosystem.

🏗️ Architecture & Technology Stack
The application is engineered around a modern monolithic SPA architecture with a strict separation of concerns:

Backend: Laravel 12 (PHP 8.2+) – Handles session management, state authorization, backend routing, and asynchronous background processing.

Frontend Bridge: Inertia.js – Eliminates the need for traditional Axios/Fetch client-side requests and duplicate routing. It seamlessly synchronizes server-side state directly into Vue props.

Frontend: Vue 3 (Composition API, <script setup>) + Vite 7 – An ultra-fast, reactive client interface providing granular control over DOM rendering and state transitions.

Styling: Tailwind CSS 4 – A utility-first framework utilized to construct a highly complex, hardware-accelerated sci-fi UI (neon glows, glitch typography, fluid layouts).

🎭 Core Architectural Concepts (The AI Theatre)
Instead of serving static data, the application simulates a living network ecosystem through the following mechanisms:

1. Entry Authentication (The Neon Gate Sub-routine)
   Upon initialization, the user is intercepted by the NeonGate.vue and NeonOverlay.vue gateway components. Clicking ENTER SYSTEM triggers an asynchronous handshake with the backend.

2. Asynchronous Background Queues (Laravel Queues & Jobs)
   To avoid blocking the main execution thread and to allow the frontend to instantly fire the intensive 3D/CSS gate-opening animation, the controller dispatches a dedicated InitializeAiTheatreJob to the background queue. This Job acts as the director for the AI agents' timeline scenario.

3. Real-Time Event Broadcasting (Laravel Reverb & Echo)
   As the background AI agents execute actions over time (generating incoming chat requests, triggering network intrusion alerts in the ALERT_CENTER, or spawning system logs), these backend events are broadcasted instantly via Laravel Reverb (the low-latency, native WebSocket server). On the client side, Laravel Echo listens to the channel and dynamically pushes new payloads into the Vue reactive state without any HTTP polling.

📁 Key Component Layout (Frontend)
Components are organized modularly, emphasizing style encapsulation and rendering performance (utilizing CSS properties like will-change and contain: strict to ensure fluid 60 FPS transitions across mobile, tablet, and desktop viewports):

Plaintext
resources/js/Pages/
├── NeonTechDashboard.vue # Main operations command center & real-time state management
└── Components/
├── NeonGate.vue # Animated mechanical wings of the entry gateway
├── NeonOverlay.vue # Fullstack Core Manifesto & AI sequence trigger
├── ObserverLogs.vue # Live stream of low-level system logs broadcasted from PHP
└── AlertCenter.vue # Crisis management interface handled by background bot actions
🛠️ Local Installation & Environment Setup
Clone the repository and install dependencies:

Bash
git clone https://github.com/RickJurrasic/neon-hub
cd neon-hub
composer install
npm install
Configure the environment file:

Bash
cp .env.example .env
php artisan key:generate
Prepare the database and run seeds:

Bash
php artisan migrate --seed
Launch development environment (Requires 3 active terminals):

Terminal 1 (App Server & Vite Compiler): php artisan serve & npm run dev

Terminal 2 (WebSocket Daemon): php artisan reverb:start

Terminal 3 (Asynchronous Queue Worker): php artisan queue:work

🗺️ Roadmap & Milestone Tracking
[x] Complete end-to-end UI/UX layout, cyberpunk typography, and 100% fluid responsiveness (Mobile, Tablet, Desktop layouts).

[x] Implement local reactive state objects for mock data handling.

[ ] Establish and configure the Laravel Reverb infrastructure layer (Current Phase).

[ ] Program autonomous AI behavior timelines (Jobs) and link with Eloquent DB models.

[ ] Deploy production build with full secure WebSockets (WSS) routing.
