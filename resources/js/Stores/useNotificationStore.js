import { defineStore } from "pinia";

export const useNotificationStore = defineStore("notifications", {
    state: () => ({
        messages: [],
        friendRequests: [],
        friends: [], // Pole pro aktivní přátelství
        alerts: [],
        isListening: false,
    }),

    getters: {
        // Zpátky vrácené gettery pro UI
        hasUnreadMessages: (state) => state.messages.some((m) => !m.read),
        hasUnreadRequests: (state) => state.friendRequests.some((r) => !r.read),
        hasUnreadAlerts: (state) => state.alerts.some((a) => !a.read),

        totalUnreadCount: (state) =>
            state.messages.filter((m) => !m.read).length +
            state.friendRequests.filter((r) => !r.read).length +
            state.alerts.filter((a) => !a.read).length,
    },

    actions: {
        // --- Hromadná hydratace systému z backendu (F5 / inicializace) ---
        hydrateSystem(stateData) {
            if (!stateData) return;

            this.friendRequests = stateData.friendships?.requests || [];
            this.friends = stateData.friendships?.active || [];
            this.messages = stateData.messages || [];
            this.alerts = stateData.alerts || [];
        },

        // --- Přidávání dat ---
        addMessage(message) {
            this.messages.push({
                id: Date.now(),
                read: false,
                time: new Date().toLocaleTimeString(),
                ...message,
            });
        },
        addFriendRequest(request) {
            // Zajistíme, že data mají správné mapování a reaktivní fallbacky, i když přijdou real-time z Echa
            this.friendRequests.push({
                id: request.id || Date.now(),
                user_id: request.user_id || request.sender_id, // Bezpečné zachycení ID uživatele
                name: request.name,
                role: request.role || "EXTERNAL_NODE",
                bio: request.bio || '"Šifrované bio prázdné."',
                trust_level: request.trust_level ?? 50,
                latency: request.latency || "24ms_STABLE",
                avatar: request.avatar || request.avatar_url, // Zachytí obě varianty pojmenování
                read: false,
                status: "pending",
            });
        },
        addAlert(alert) {
            this.alerts.push({
                id: Date.now(),
                read: false,
                type: "alert",
                ...alert,
            });
        },
        addFriend(friend) {
            this.friends.push({
                id: friend.id,
                user_id: friend.user_id,
                name: friend.name,
                role: friend.role || "EXTERNAL_NODE",
                bio: friend.bio || '"Šifrované bio prázdné."',
                trust_level: friend.trust_level ?? 50,
                latency: friend.latency || "24ms_STABLE",
                avatar: friend.avatar,
                status: "accepted",
            });
        },

        // --- Logika správy přátelství ---
        updateFriendRequestStatus(id, newStatus) {
            const index = this.friendRequests.findIndex((r) => r.id === id);
            if (index !== -1) {
                const req = this.friendRequests[index];
                req.status = newStatus;
                req.read = true;

                if (newStatus === "accepted") {
                    // Použijeme addFriend metodu pro správné vyčištění objektu
                    this.addFriend(req);
                    this.friendRequests.splice(index, 1);
                }
            }
        },
        removeFriendRequest(id) {
            this.friendRequests = this.friendRequests.filter(
                (r) => r.id !== id,
            );
        },
        removeFriend(id) {
            this.friends = this.friends.filter(
                (f) => f.id !== id && f.user_id !== id,
            );
        },

        // --- Přečtení notifikací ---
        markMessagesAsRead() {
            this.messages.forEach((m) => (m.read = true));
        },
        markRequestsAsRead() {
            this.friendRequests.forEach((r) => (r.read = true));
        },
        markAlertsAsRead() {
            this.alerts.forEach((a) => (a.read = true));
        },

        // --- Real-time propojení ---
        initListeners(userId) {
            if (this.isListening) return;
            this.isListening = true;

            window.Echo.private(`App.Models.User.${userId}`)
                .listen("FriendRequestReceived", (e) => {
                    // Očekává se, že tvůj Laravel Event posílá v poli 'data' kompletní profil odesílatele
                    this.addFriendRequest(e.data);
                })
                .listen("FriendshipAccepted", (e) => {
                    this.updateFriendRequestStatus(e.friendshipId, "accepted");
                });
        },

        // --- Reset ---
        resetAll() {
            this.$reset();
            this.isListening = false;
        },
    },
});
