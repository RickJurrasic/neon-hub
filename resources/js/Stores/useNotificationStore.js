import { defineStore } from "pinia";
import axios from "axios";

export const useNotificationStore = defineStore("notifications", {
    state: () => ({
        messages: [],
        friendRequests: [],
        friends: [],
        alerts: [],
        posts: [],
        likeNotifications: [],
        commentNotifications: [],
                isListening: false,
        currentUserId: null,
        isRateLimited: false,
        rateLimitRetry: 0,
        rateLimitTimer: null,
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

        hasUnreadLikes: (state) =>
            state.likeNotifications.some(
                (n) => n.read !== true && n.read !== 1 && n.read !== "1",
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
            ).length +
            state.likeNotifications.filter(
                (n) => n.read !== true && n.read !== 1 && n.read !== "1",
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

                const messagesArray = Array.isArray(response.data)
                    ? response.data
                    : response.data.data || [];

                const existingIds = new Set(this.messages.map((m) => m.id));
                const newMessages = messagesArray.filter(
                    (m) => !existingIds.has(m.id),
                );
                this.messages = [...this.messages, ...newMessages];
            } catch (error) {
                console.error("Failed to fetch messages:", error);
            }
        },

                clearRateLimit() {
            this.isRateLimited = false;
            this.rateLimitRetry = 0;
            if (this.rateLimitTimer) {
                clearInterval(this.rateLimitTimer);
                this.rateLimitTimer = null;
            }
        },

        async sendReply(messageId, text) {
            try {
                const response = await axios.post("/messages", {
                    message_id: messageId,
                    text: text,
                });

                const messageData = response.data.data || response.data;

                const myMessage = {
                    ...messageData,
                    sender: "YOU",
                    role: "user",
                    read: true,
                };
                this.addMessage(myMessage);
                this.clearRateLimit();
                return true;
            } catch (error) {
                if (error?.response?.status === 429) {
                    const retryAfter =
                        parseInt(error.response.headers?.["retry-after"] ?? "0", 10) || 0;

                    this.isRateLimited = true;
                    this.rateLimitRetry = retryAfter;

                    if (this.rateLimitTimer) clearInterval(this.rateLimitTimer);
                    this.rateLimitTimer = setInterval(() => {
                        this.rateLimitRetry = Math.max(0, this.rateLimitRetry - 1);
                        if (this.rateLimitRetry <= 0) {
                            this.isRateLimited = false;
                            clearInterval(this.rateLimitTimer);
                            this.rateLimitTimer = null;
                        }
                    }, 1000);

                    console.warn(
                        "AI_RATE_LIMITED: interactive budget exhausted, retrying in " +
                            retryAfter + "s."
                    );
                } else {
                    console.error("Failed to forward response:", error);
                }

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
                await axios.delete(`/conversations/${conversationId}`);

                this.messages = this.messages.filter(
                    (m) => String(m.conversation_id) !== String(conversationId),
                );
                return true;
            } catch (error) {
                console.error("Failed to purge conversation node:", error);
                this.fetchMessages();
                return false;
            }
        },

        // --- AKCE PRO PŘÁTELSTVÍ ---
        async acceptFriendRequest(id) {
            try {
                // Laravel očekává PATCH /friendships/{id} na model Friendship
                await axios.patch(`/friendships/${id}`, { status: "accepted" });
                this.updateFriendRequestStatus(id, "accepted");
                return true;
            } catch (error) {
                console.error("Failed to accept friend request:", error);
                return false;
            }
        },

        async declineFriendRequest(id) {
            try {
                // Smazání přes DELETE /friendships/{id} na model Friendship
                await axios.delete(`/friendships/${id}`);
                this.removeFriendRequest(id);
                return true;
            } catch (error) {
                console.error("Failed to decline friend request:", error);
                return false;
            }
        },
        // ---------------------------------

        hydrateSystem(stateData) {
            if (!stateData) return;

            const requestsData = stateData.friendships?.requests || stateData.friendRequests || [];
            this.friendRequests = requestsData.map(
                (r) => ({
                    ...r,
                    id: r.pivot?.id || r.friendship_id || r.id,
                    user_id: r.sender_id || r.user_id || r.id,
                    read: !!r.read,
                }),
            );

            const activeData = stateData.friendships?.active || stateData.friends || [];
            this.friends = activeData.map(
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

            const msg = message.data || message.message || message;
            const isOwnMessage = msg.role === "user" || msg.sender === "YOU";

            const normalized = {
                id: msg.id || Date.now(),
                conversation_id: msg.conversation_id || msg.conversationId,
                text: msg.text || msg.content || "",
                sender: isOwnMessage ? "YOU" : (msg.agent_name || msg.sender || "SYSTEM_BOT"),
                agent_name: msg.agent_name || "",
                read: isOwnMessage
                    ? true
                    : msg.read === true ||
                      msg.read === 1 ||
                      msg.read === "1" ||
                      false,
                time: msg.time || new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
                created_at: msg.created_at || new Date().toISOString(),
            };

            if (!this.messages.some((m) => String(m.id) === String(normalized.id))) {
                this.messages.push(normalized);
            }
        },

        addFriendRequest(request) {
            this.friendRequests.push({
                ...request,
                id: request.pivot?.id || request.friendship_id || request.id || Date.now(),
                user_id: request.sender_id || request.user_id || request.id,
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
                ...friend, 
                id: friend.pivot?.id || friend.friendship_id || friend.id,
                user_id: friend.user_id || friend.id,
                status: "accepted" 
            });
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

        addCommentNotification(comment, postId, postOwnerId, userId = null) {
            const commenterName =
                comment.author?.name || comment.author || "BOT";

            const post = this.posts.find(
                (p) => String(p.id) === String(postId),
            );
            const postAuthorName = post?.author?.name || post?.author || "user";

            const isUserAction = Number(userId) === Number(this.currentUserId);
            const actionPrefix = isUserAction
                ? "USER_ACTION: COMMENT"
                : "AI_ACTION: COMMENT";

            const customMessage = `${commenterName} commented on a post of user ${postAuthorName}`;

            this.addAlert({
                id: Date.now(),
                type: "comment",
                title: actionPrefix,
                msg: customMessage,
                postId: postId,
                read: false,
                timestamp: new Date().toISOString(),
            });
        },

        addLikeNotification(
            postId,
            userId,
            userName,
            targetAuthor = "užívala",
            isLiked = true,
        ) {
            const name = userName || "BOT";
            const customMessage = isLiked
                ? `${name} liked the post of user ${targetAuthor}`
                : `${name} unliked the post of user ${targetAuthor}`;

            const alertType = isLiked ? "like" : "unlike";
            const isUserAction = userId != null && Number(userId) === Number(this.currentUserId);
            const actionPrefix = isUserAction
                ? "USER_ACTION: LIKE"
                : "AI_ACTION: LIKE";
            const alertTitle = isLiked
                ? actionPrefix
                : actionPrefix.replace("LIKE", "UNLIKE");

            this.likeNotifications.push({
                id: Date.now(),
                type: alertType,
                post_id: postId,
                user_id: userId,
                user_name: name,
                message: customMessage,
                read: false,
                timestamp: new Date().toISOString(),
            });

            this.addAlert({
                id: Date.now(),
                type: alertType,
                title: alertTitle,
                msg: customMessage,
                postId: postId,
                read: false,
                timestamp: new Date().toISOString(),
            });
        },

        removeFriend(id) {
            this.friends = this.friends.filter(
                (f) => String(f.id) !== String(id),
            );
        },

        removeFriendRequest(id) {
            this.friendRequests = this.friendRequests.filter(
                (r) => String(r.id) !== String(id),
            );
        },

        updateFriendRequestStatus(id, newStatus) {
            const req = this.friendRequests.find(
                (r) => String(r.id) === String(id),
            );

            if (req) {
                req.status = newStatus;
                req.read = true;

                if (newStatus === "accepted") {
                    this.addFriend(req);
                    this.removeFriendRequest(id);
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

        markLikesAsRead() {
            this.likeNotifications.forEach((n) => (n.read = true));
        },

        initListeners(userId) {
            if (this.isListening) return;
            this.isListening = true;
            this.currentUserId = userId;

            window.Echo.private(`App.Models.User.${userId}`)
                .listen(".FriendRequestReceived", (e) =>
                    this.addFriendRequest(e.data || e.request || e.friendship || e),
                )
                .listen(".FriendshipAccepted", (e) =>
                    this.updateFriendRequestStatus(e.friendshipId, "accepted"),
                )
                .listen(".MessageReceived", (e) =>
                    this.addMessage(e.data || e.message),
                )
                .listen(".NewActivityAlert", (e) => {
                    this.addAlert({ title: "SYSTEM_ALERT", msg: e.message });
                })
                .listen(".PostCreated", (e) => this.addPost(e.data || e.post))
                .listen(".PostLiked", (e) => {
                    const post = this.posts.find(
                        (p) => String(p.id) === String(e.postId),
                    );
                    if (post) post.likes_count = e.likesCount;

                    const isUserAction =
                        e.userId != null && Number(e.userId) === Number(this.currentUserId);

                    this.addLikeNotification(
                        e.postId,
                        e.userId,
                        e.userName,
                        post?.author?.name || post?.author || "user",
                        e.isLiked,
                    );
                })
                .listen(".CommentCreated", (e) => {
                    const demoOwnerId = e.comment?.demo_owner_id ?? null;
                    const isOwn = Number(demoOwnerId) === Number(this.currentUserId);
                    if (isOwn) {
                        this.addCommentToPost(e.postId, e.comment);
                        this.addCommentNotification(
                            e.comment,
                            e.postId,
                            e.postOwnerId,
                            e.userId,
                            true,
                        );
                    }
                });

            window.Echo.channel("posts")
                .listen(".PostCreated", (e) => this.addPost(e.data || e.post))
                .listen(".CommentCreated", (e) => {
                    const demoOwnerId = e.comment?.demo_owner_id ?? null;
                    const isGlobal = demoOwnerId === null;
                    const isOwn = Number(demoOwnerId) === Number(this.currentUserId);
                    if (isGlobal || isOwn) {
                        this.addCommentToPost(e.postId, e.comment);
                    }
                    const isUserAction = Number(e.userId) === Number(this.currentUserId);
                    if (isUserAction) {
                        this.addCommentNotification(
                            e.comment,
                            e.postId,
                            e.postOwnerId,
                            e.userId,
                            isUserAction,
                        );
                    }
                });
        },
    },
});