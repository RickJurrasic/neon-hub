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
        hasUnreadMessages: (state) =>
            state.messages.some(
                (m) => m.read === false || m.read === 0 || m.read === "0",
            ),

        hasUnreadRequests: (state) =>
            state.friendRequests.some(
                (r) => r.read === false || r.read === 0 || r.read === "0",
            ),

        hasUnreadAlerts: (state) =>
            state.alerts.some(
                (a) => a.read !== true && a.read !== 1 && a.read !== "1",
            ),

        totalUnreadCount: (state) =>
            state.messages.filter(
                (m) => m.read === false || m.read === 0 || m.read === "0",
            ).length +
            state.friendRequests.filter(
                (r) => r.read === false || r.read === 0 || r.read === "0",
            ).length +
            state.alerts.filter(
                (a) => a.read !== true && a.read !== 1 && a.read !== "1",
            ).length,
    },

    actions: {
        async toggleLike(post) {
            if (post.is_liked === undefined) post.is_liked = false;

            if (post.likes_count === undefined) post.likes_count = 0;

            const originalIsLiked = post.is_liked;

            const originalCount = post.likes_count;

            post.is_liked = !post.is_liked;

            post.likes_count = originalCount + (post.is_liked ? 1 : -1);

            try {
                if (post.is_liked) await axios.post(`/posts/${post.id}/like`);
                else await axios.delete(`/posts/${post.id}/like`);
            } catch (error) {
                console.error("Failed to sync pulse reaction:", error);

                post.is_liked = originalIsLiked;

                post.likes_count = originalCount;
            }
        },

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

        async sendComment(postId, text) {
            try {
                const response = await axios.post(`/posts/${postId}/comments`, {
                    content: text,
                });

                this.addCommentToPost(postId, response.data);

                return true;
            } catch (error) {
                console.error("Failed to push comment stream:", error);

                return false;
            }
        },

        async deleteConversation(conversationId) {
            try {
                this.messages = this.messages.filter(
                    (m) => String(m.conversation_id) !== String(conversationId),
                );

                await axios.delete(`/conversations/${conversationId}`);
            } catch (error) {
                console.error("Failed to purge conversation node:", error);

                this.fetchMessages();
            }
        },

        hydrateSystem(stateData) {
            if (!stateData) return;

            this.friendRequests = (stateData.friendships?.requests || []).map(
                (r) => ({ ...r, read: !!r.read }),
            );

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

            this.alerts = (stateData.alerts || []).map((a) => ({
                ...a,

                read: a.read === true || a.read === 1 || a.read === "1",
            }));

            if (stateData.posts) this.posts = stateData.posts;
        },

        addMessage(message) {
            if (!message) return;

            const normalized = {
                id: message.id || Date.now(),

                conversation_id:
                    message.conversation_id || message.conversationId || null,

                text: message.text || message.content || "",

                sender: message.sender || message.role || "UNKNOWN",

                read: false,
            };

            if (!this.messages.some((m) => m.id === normalized.id))
                this.messages.push(normalized);
        },

        addFriendRequest(request) {
            this.friendRequests.push({
                ...request,

                id: request.id || Date.now(),

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
            this.friends.push({ ...friend, status: "accepted" });
        },

        addPost(post) {
            if (!post) return;

            if (this.posts.some((p) => p.id === post.id)) return;

            this.posts.unshift(post);
        },

        addCommentToPost(postId, comment) {
            const post = this.posts.find(
                (p) => String(p.id) === String(postId),
            );

            if (post) {
                if (!post.comments) post.comments = [];

                // TADY JE OPRAVA: Normalizujeme příchozí komentář tak, aby vždy měl všechna pole

                const normalizedComment = {
                    id: comment.id || Date.now(),

                    author: comment.author?.name || comment.author || "YOU",

                    text: comment.text || comment.content || "",

                    timestamp:
                        comment.timestamp ||
                        new Date().toLocaleTimeString([], {
                            hour: "2-digit",

                            minute: "2-digit",
                        }),

                    can_edit: true,
                };

                const exists = post.comments.some(
                    (c) => String(c.id) === String(normalizedComment.id),
                );

                if (!exists) {
                    post.comments.push(normalizedComment);

                    post.comments_count = (post.comments_count || 0) + 1;
                }
            }
        },

        addCommentNotification(comment, postId) {
            this.addAlert({
                id: Date.now(),

                type: "comment",

                title: "NEW COMMENT",

                msg: `${comment.author} commented on a post.`,

                postId: postId,

                read: false,

                timestamp: new Date().toISOString(),
            });
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
            const req = this.friendRequests.find((r) => r.id === id);

            if (req) {
                req.status = newStatus;

                req.read = true;

                if (newStatus === "accepted") this.addFriend(req);
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

            this.currentUserId = userId; // ← přidáno

            window.Echo.private(`App.Models.User.${userId}`)

                .listen("FriendRequestReceived", (e) =>
                    this.addFriendRequest(e.data),
                )

                .listen("FriendshipAccepted", (e) =>
                    this.updateFriendRequestStatus(e.friendshipId, "accepted"),
                )

                .listen("MessageReceived", (e) =>
                    this.addMessage(e.data || e.message),
                )

                .listen(".PostCreated", (e) => this.addPost(e.data || e.post))

                .listen(".App\\Events\\NewActivityAlert", (e) => {
                    this.addAlert({ title: "SYSTEM_ALERT", msg: e.message });
                });

            window.Echo.channel("posts")

                .listen(".PostLiked", (e) => {
                    const post = this.posts.find(
                        (p) => String(p.id) === String(e.postId),
                    );

                    if (post) post.likes_count = e.likesCount;
                })

                .listen(".CommentCreated", (e) => {
                    this.addCommentToPost(e.postId, e.comment);

                    // Každý komentář → notifikace pro přihlášeného uživatele

                    this.addCommentNotification(e.comment, e.postId);
                });
        },
    },
});
