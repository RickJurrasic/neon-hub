import { defineStore } from "pinia";

export const useNotificationStore = defineStore("notifications", {
    state: () => ({
        messages: [],
        friendRequests: [],
        friends: [], // Nové pole pro aktivní přátelství
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
            this.friendRequests.push({
                id: request.id || Date.now(),
                read: false,
                status: "pending",
                ...request,
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
            this.friends.push(friend);
        },

        // --- Logika správy přátelství ---
        updateFriendRequestStatus(id, newStatus) {
            const index = this.friendRequests.findIndex((r) => r.id === id);
            if (index !== -1) {
                const req = this.friendRequests[index];
                req.status = newStatus;
                req.read = true;

                if (newStatus === "accepted") {
                    this.friends.push(req);
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
            this.friends = this.friends.filter((f) => f.id !== id);
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
                .listen("FriendRequestReceived", (e) =>
                    this.addFriendRequest(e.data),
                )
                .listen("FriendshipAccepted", (e) =>
                    this.updateFriendRequestStatus(e.friendshipId, "accepted"),
                );
        },

        // --- Reset ---
        resetAll() {
            this.$reset();
            this.isListening = false;
        },
    },
});
