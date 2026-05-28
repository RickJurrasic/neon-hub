import { useNotificationStore } from '@/Stores/useNotificationStore';

export const initNotificationService = (userId) => {
    const store = useNotificationStore();

    window.Echo.private(`App.Models.User.${userId}`)
        .listen('MessageReceived', (e) => {
            console.log('📩 Nová zpráva přijata:', e);

            store.addMessage({
                id: Date.now(),
                sender: e.data.sender || 'UNKNOWN_SOURCE',
                text: e.data.text,
                time: new Date().toLocaleTimeString(),
                read: false
            });
        })
        .listen('SystemAlertTriggered', (e) => {
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
