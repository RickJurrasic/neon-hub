import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notifications', {
    state: () => ({
        messages: [],
        friendRequests: [],
        alerts: [],
    }),

    getters: {
        hasUnreadMessages: (state) => state.messages.some(m => !m.read),
        hasUnreadRequests: (state) => state.friendRequests.some(r => !r.read),
        hasUnreadAlerts: (state) => state.alerts.some(a => !a.read),

        totalUnreadCount: (state) =>
            state.messages.filter(m => !m.read).length +
            state.friendRequests.filter(r => !r.read).length +
            state.alerts.filter(a => !a.read).length
    },

    actions: {
        // Pomocné akce pro přidávání s vynucenou strukturou
        addMessage(message) {
            this.messages.push({
                id: Date.now(),
                read: false,
                time: new Date().toLocaleTimeString(),
                ...message
            });
        },
        addFriendRequest(request) {
            this.friendRequests.push({
                id: Date.now(),
                read: false,
                ...request
            });
        },
        addAlert(alert) {
    this.alerts.push({
        id: Date.now(),
        read: false,
        type: 'alert',
        title: alert.title || 'SYSTEM_ALERT', // Přidej defaultní title
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), // Přidej čas
        ...alert
    });
},

        // Tato metoda propojí tvůj store s Reverbem
        initListeners(userId) {
    if (this.isListening) return;
    this.isListening = true;

    window.Echo.private(`App.Models.User.${userId}`)
        .listen('FriendRequestReceived', (e) => { this.addFriendRequest(e.data); })
        .listen('MessageReceived', (e) => { this.addMessage(e.data); })
        .listen('.SystemAlertTriggered', (e) => {
    const payload = e.data?.data?.message || e.message || 'Krizový stav aktivován!';

    this.addAlert({
        title: 'KRIZOVÝ_STAV',
        msg: payload
    });
});
},

        // Označování za přečtené
        markMessagesAsRead() {
            this.messages.forEach(m => m.read = true);
        },
        markRequestsAsRead() {
            this.friendRequests.forEach(r => r.read = true);
        },
        markAlertsAsRead() {
            this.alerts.forEach(a => a.read = true);
        },

        // Důležité pro CTO: Vyčištění storu při odhlášení (prevence memory leaků)
        resetAll() {
            this.$reset();
        }
    }
});
