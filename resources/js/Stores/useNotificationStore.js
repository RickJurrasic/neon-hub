import { defineStore } from "pinia";
import axios from "axios";

export const useNotificationStore = defineStore("notifications", {
    state: () => ({
        messages: [],
        friendRequests: [],
        friends: [],
        alerts: [],
        posts: [],
        isListening: false,
    }),

    getters: {
        // FIX: Striktně kontrolujeme, zda hodnota odpovídá nepřečtenému stavu
        hasUnreadMessages: (state) =>
            state.messages.some(
                (m) => m.read === false || m.read === 0 || m.read === "0",
            ),
        hasUnreadRequests: (state) => state.friendRequests.some((r) => !r.read),
        hasUnreadAlerts: (state) => state.alerts.some((a) => !a.read),

        totalUnreadCount: (state) =>
            state.messages.filter(
                (m) => m.read === false || m.read === 0 || m.read === "0",
            ).length +
            state.friendRequests.filter((r) => !r.read).length +
            state.alerts.filter((a) => !a.read).length,
    },

    actions: {
        async fetchMessages() {
            try {
                const response = await axios.get(route("messages.index"));
                this.messages = response.data;
            } catch (error) {
                console.error("Failed to fetch messages:", error);
            }
        },

        async sendReply(messageId, text) {
            try {
                const response = await axios.post("/messages", {
                    message_id: messageId,
                    text: text,
                });
                this.addMessage(response.data);
                return true;
            } catch (error) {
                console.error("Failed to forward response:", error);
                return false;
            }
        },

        async deleteConversation(conversationId) {
            try {
                // 1. Okamžitě vymažeme z frontendu VŠECHNY zprávy patřící do této konverzace
                this.messages = this.messages.filter(
                    (m) => String(m.conversation_id) !== String(conversationId),
                );

                // 2. Pošleme požadavek na backend s ID konverzace
                await axios.delete(`/conversations/${conversationId}`);
            } catch (error) {
                console.error("Failed to purge conversation node:", error);
                // Pokud backend selže, pro jistotu natáhneme data znovu, ať neodpovídají realitě
                this.fetchMessages();
            }
        },

        hydrateSystem(stateData) {
            if (!stateData) return;
            this.friendRequests = stateData.friendships?.requests || [];

            this.friends = (stateData.friendships?.active || []).map(
                (friend) => ({
                    ...friend,
                    id: friend.pivot?.id || friend.friendship_id || friend.id,
                    user_id: friend.user_id || friend.id,
                }),
            );

            this.messages = (stateData.messages || []).map((m) => ({
                ...m,
                read: m.read === true || m.read === 1 || m.read === "1",
            }));

            this.alerts = stateData.alerts || [];

            // TADY HYDRATUJEME POSTY Z NEONHUBCONTROLLERU
            if (stateData.posts) {
                this.posts = stateData.posts;
            }
        },

        addMessage(message) {
            if (!message) return;

            const normalized = {
                id: message.id || Date.now(),
                conversation_id:
                    message.conversation_id || message.conversationId || null,
                text: message.text || message.content || "",
                sender: message.sender || message.role || "UNKNOWN",
                agent_name: message.agent_name || message.agentName || null,
                time:
                    message.time ||
                    (message.created_at
                        ? new Date(message.created_at)
                              .toTimeString()
                              .split(" ")[0]
                        : new Date().toTimeString().split(" ")[0]),
                created_at: message.created_at || new Date().toISOString(),

                // FIX: Striktní převod na boolean, aby neproklouzlo 0 / "0"
                read:
                    message.read === true ||
                    message.read === 1 ||
                    message.read === "1",
            };

            const exists = this.messages.some((m) => m.id === normalized.id);
            if (exists) return;

            this.messages.push(normalized);
        },

        addFriendRequest(request) {
            this.friendRequests.push({
                id: request.id || Date.now(),
                user_id: request.user_id || request.sender_id,
                name: request.name,
                role: request.role || "EXTERNAL_NODE",
                bio: request.bio || '"Šifrované bio prázdné."',
                trust_level: request.trust_level ?? 50,
                latency: request.latency || "24ms_STABLE",
                avatar: request.avatar || request.avatar_url,
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

        addPost(post) {
            if (!post) return;
            const exists = this.posts.some((p) => p.id === post.id);
            if (exists) return;

            // Nový post hodíme na začátek pole (unshift), aby byl nahoře
            this.posts.unshift(post);
        },

        removeFriend(id) {
            this.friends = this.friends.filter((f) => f.id !== id);
        },

        removeFriendRequest(id) {
            this.friendRequests = this.friendRequests.filter(
                (r) => r.id !== id,
            );
        },

        updateFriendRequestStatus(id, newStatus) {
            const index = this.friendRequests.findIndex((r) => r.id === id);
            if (index !== -1) {
                const req = this.friendRequests[index];
                req.status = newStatus;
                req.read = true;
                if (newStatus === "accepted") {
                    this.addFriend(req);
                    this.friendRequests.splice(index, 1);
                }
            }
        },

        markMessagesAsRead() {
            this.messages.forEach((m) => (m.read = true));
        },
        markRequestsAsRead() {
            this.friendRequests.forEach((r) => (r.read = true));
        },
        markAlertsAsRead() {
            this.alerts.forEach((a) => (a.read = true));
        },

        initListeners(userId) {
            if (this.isListening) return;
            this.isListening = true;

            window.Echo.private(`App.Models.User.${userId}`)
                .listen("FriendRequestReceived", (e) =>
                    this.addFriendRequest(e.data),
                )
                .listen("FriendshipAccepted", (e) =>
                    this.updateFriendRequestStatus(e.friendshipId, "accepted"),
                )
                .listen("MessageReceived", (e) => {
                    // Ošetření struktury payloadu (podpora e.data i e.message z backendu)
                    const incomingMessage = e.data || e.message;
                    if (incomingMessage) {
                        this.addMessage(incomingMessage);
                    }
                })

                .listen(".PostCreated", (e) => {
                    const incomingPost = e.data || e.post;
                    if (incomingPost) {
                        this.addPost(incomingPost); // Prásk! A vyskočí modrý banner "Nové příspěvky"
                    }
                });
        },
    },
});
