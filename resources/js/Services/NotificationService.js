import { useNotificationStore } from '@/Stores/useNotificationStore';

export const initNotificationService = (userId) => {
    const store = useNotificationStore();

    console.log(`📡 Inicializace Echo pro kanál: App.Models.User.${userId}`);

    // Důležité: Tečka před názvem eventu je nutná, pokud Laravel posílá plný namespace
    window.Echo.private(`App.Models.User.${userId}`)
        .listen('.App\\Events\\MessageReceived', (e) => {
    console.log('📩 Nová zpráva přijata:', e);

    store.addMessage({
        id: Date.now(), // Důležité pro :key v v-for
        sender: e.data.sender || 'UNKNOWN_SOURCE',
        text: e.data.text,
        time: new Date().toLocaleTimeString(), // Generujeme čas na frontendu
        read: false
    });
})
        .listen('.App\\Events\\FriendRequestReceived', (e) => {
    console.log('🤝 Nová žádost o přátelství:', e);
    store.addFriendRequest({
        id: e.data.id,
        name: e.data.name,
        read: false // Pinia store se postará o defaultní stav
    });
})
        .listen('.App\\Events\\SystemAlertTriggered', (e) => {
            console.log('📡 Systémový alert zachycen:', e.message);
            store.addAlert({
                id: Date.now(),
                title: 'SYSTEM_ALERT',
                msg: e.message,
                type: 'alert'
            });
        })
        .error((error) => {
            console.error('❌ Echo chyba:', error);
        });
};
