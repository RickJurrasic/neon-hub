<div align="center">

  <h1>🌌 NeonHub</h1>
  <h3>Real-Time Cyberpunk Social Platform • Laravel 13 + Vue 3</h3>

  <p>
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
    <img src="https://img.shields.io/badge/Vue.js-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white" alt="Vue">
    <img src="https://img.shields.io/badge/Reverb-Real--time-06B6D4?style=for-the-badge" alt="Reverb">
    <img src="https://img.shields.io/badge/Inertia.js-5F4B8B?style=for-the-badge&logo=inertia&logoColor=white" alt="Inertia">
  </p>

  <img src="preview.png" alt="NeonHub Preview" width="820" style="border-radius: 12px; margin: 20px 0;">

  <p><strong>Status:</strong> Alpha • <strong>Built by:</strong> [Your Name]</p>

</div>

---

## About The Project

**NeonHub** is a fully-featured real-time social network with a strong cyberpunk aesthetic. It showcases modern full-stack development using **Laravel 13** as the backbone and a reactive **Vue 3** frontend.

The platform allows users to post system logs, engage with live content (likes & comments), manage encrypted friendships, and interact with an autonomous AI agent called **SENTINEL_01**.

This project was created while working in **Fintech Tech Support at GoPay** as a way to deepen my skills and demonstrate that I can design, build, and ship production-grade features.

**Goal**: Transition into a Laravel / Full-Stack Developer role.

---

## ✨ Key Features

- Real-time Social Feed with instant likes and comments
- Autonomous AI Agent (`SENTINEL_01`) using Laravel AI + Gemini/Groq
- Friendship system with real-time request handling
- Secure messaging with AI agent
- Immersive cyberpunk UI (glassmorphism, neon animations, gate entrance)
- Live activity alerts and notifications

---

## 🛠️ Tech Stack

- **Backend**: Laravel 13, Reverb (WebSockets), Laravel AI, Queues, Events, Actions
- **Frontend**: Vue 3 (Composition API), Pinia, Tailwind CSS 4, Inertia.js
- **Database**: SQLite / MySQL
- **Testing**: PestPHP
- **Monitoring**: Laravel Pulse (planned)

---

## Roadmap

| Status         | Feature                        | Description                                      |
| -------------- | ------------------------------ | ------------------------------------------------ |
| ✅ Done        | Cyberpunk Immersive UI         | Neon effects, animations, responsive mobile dock |
| ✅ Done        | Real-time Social Feed          | Posts, likes, comments via Reverb                |
| ✅ Done        | Friendship System              | Pending & accepted connections                   |
| ✅ Done        | AI Agent (`SENTINEL_01`)       | Auto-greeting, posts, friend requests            |
| ✅ Done        | Secure Messaging               | Real-time chat with AI                           |
| ✅ Done        | Event-Driven Architecture      | Events, Jobs, Broadcasting                       |
| ✅ Done        | Auto Demo Login                | Seamless onboarding                              |
| 🔄 In Progress | Comment Notifications          | Real-time + in-app notifications                 |
| 🔄 In Progress | Laravel Pulse Integration      | Into NeonTechDashboard                           |
| 🔄 In Progress | AI Agents Abstraction          | Support multiple agents                          |
| 🔄 In Progress | Major Refactor                 | Slim down fat controllers, add Services          |
| ⬜ Planned     | Authorization Policies & Gates | Full access control                              |
| ⬜ Planned     | API Documentation              | Laravel Scribe                                   |
| ⬜ Planned     | Activity Logging               | Spatie package                                   |
| ⬜ Planned     | Live Public Demo               | Deployment                                       |

---

## 🚀 Quick Start

```bash
git clone https://github.com/rickjurrasic/neon-hub.git
cd neon-hub

composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate --seed
npm run dev
```

<h3> Run the development server: </h3>
```bash
php artisan serve
```
<h3> Run Reverb in another terminal: </h3>
```bash
php artisan reverb:start
```
<hr>
<h3> Screenshots: </h3>
<img src="preview.png" alt="Feed"><img src="preview2.png" alt="Messages"><img src="preview3.png" alt="Profile">
