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
        async toggleLike(post) {
            // POJISTKA: Pokud agent poslal post bez těchto klíčů, inicializujeme je
            if (post.is_liked === undefined) post.is_liked = false;
            if (post.likes_count === undefined) post.likes_count = 0;

            const originalIsLiked = post.is_liked;
            const originalCount = post.likes_count;

            post.is_liked = !post.is_liked;
            post.likes_count = originalCount + (post.is_liked ? 1 : -1);

            try {
                if (post.is_liked) {
                    await axios.post(`/posts/${post.id}/like`);
                } else {
                    await axios.delete(`/posts/${post.id}/like`);
                }
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

            const normalizedPost = {
                id: post.id,
                author: post.author?.name || post.author || "EXTERNAL_NODE",
                content: post.content,
                type: post.type || "DEFAULT",
                time: post.latency || post.time || "0.0ms",
                likes_count: post.likes_count || 0,
                is_liked: post.is_liked || false,
                comments_count: post.comments_count || 0,
                image: post.image_url || post.image || null,
                image_meta: post.image_meta || null,
                comments: Array.isArray(post.comments) ? post.comments : [],
            };

            this.posts.unshift(normalizedPost);
        },

        addCommentToPost(postId, comment) {
            const post = this.posts.find(
                (p) => String(p.id) === String(postId),
            );
            if (post) {
                // POJISTKA: Pokud pole comments neexistuje, reaktivně ho vytvoříme
                if (!post.comments) {
                    post.comments = [];
                }

                const exists = post.comments.some(
                    (c) => String(c.id) === String(comment.id),
                );
                if (!exists) {
                    post.comments.push({
                        id: comment.id,
                        author: comment.author?.name || comment.author || "YOU",
                        text: comment.content || comment.text,
                        timestamp:
                            comment.timestamp ||
                            new Date(
                                comment.created_at || Date.now(),
                            ).toLocaleTimeString([], {
                                hour: "2-digit",
                                minute: "2-digit",
                            }),
                        can_edit: true,
                    });

                    // POJISTKA: Totéž pro počítadlo komentářů
                    post.comments_count = (post.comments_count || 0) + 1;
                }
            }
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
                    const incomingMessage = e.data || e.message;
                    if (incomingMessage) {
                        this.addMessage(incomingMessage);
                    }
                })
                .listen(".PostCreated", (e) => {
                    const incomingPost = e.data || e.post;
                    if (incomingPost) {
                        this.addPost(incomingPost);
                    }
                });

            window.Echo.channel("posts")
                .listen(".PostLiked", (e) => {
                    const post = this.posts.find(
                        (p) => String(p.id) === String(e.postId),
                    );
                    if (post) {
                        post.likes_count = e.likesCount;
                    }
                })
                .listen(".CommentCreated", (e) => {
                    this.addCommentToPost(e.postId, e.comment);
                });
        },
    },
});
