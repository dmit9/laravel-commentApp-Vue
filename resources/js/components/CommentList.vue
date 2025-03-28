<template>
    <div class="container">
        <h2>Заглавные комментарии</h2>
        <div class="header-btn">
            <div>
                <button @click="sortComments('user_name')">Имя пользователя</button>
                <button @click="sortComments('email')">E-mail</button>
                <button @click="sortComments('created_at')">Дата добавления</button>
            </div>
            <div>
                <button @click="showCreatePostForm">Написать комментарий</button>
            </div>
        </div>
        <table>
            <thead>
            <tr>
                <th>Имя</th>
                <th>Email</th>
                <th>Дата</th>
                <th>Комментарий</th>
                <th>Действие</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="comment in comments.data" :key="comment.id">
                <tr>
                    <td>
                        <img :src="comment.image_path ? '/storage/' + comment.image_path : '/storage/default.jpg'" alt="Avatar" class="avatar"/>
                        {{ comment.user.name }}
                    </td>
                    <td>{{ comment.user.email }}</td>
                    <td>{{ formatDate(comment.created_at) }}</td>
                    <td>{{ comment.text }}</td>
                    <td>
                        <button @click="replyToComment(comment.id)">Ответить</button>
                        <button @click="toggleReplies(comment.id, '')">
                            {{ expandedComments[comment.id] ? 'Скрыть ответы' : 'Развернуть ответы' }}
                        </button>
                    </td>
                </tr>
                <tr v-if="expandedComments[comment.id] && currentReplies.data?.length">
                    <td colspan="5">
                        <div class="replies">
                            <div v-for="(reply, index) in currentReplies.data" 
                                 :key="reply.id" 
                                 class="reply"
                                 :style="{ width: `${100 - (index * 2)}%` }"
                            >
                                <div class="reply-header">
                                    <div>
                                        <img :src="reply.image_path ? '/storage/' + reply.image_path : '/storage/default.jpg'" alt="Reply Image" class="avatar"/>
                                    </div>
                                    <div><strong>{{ reply.user.name }}</strong></div>
                                    <div>{{ formatDate(reply.created_at) }}</div>
                                    <button @click="replyToComment(comment.id)" class="reply-btn">Ответить</button>
                                </div>
                                <p>{{ reply.text }}</p>
                            </div>
                            <div v-if="currentReplies.links && currentReplies.last_page > 1" class="pagination">
                                <button
                                    @click="loadRepliesPage(currentReplies.prev_page_url)"
                                    :disabled="!currentReplies.prev_page_url">
                                    Назад
                                </button>
                                <button
                                    @click="loadRepliesPage(currentReplies.next_page_url)"
                                    :disabled="!currentReplies.next_page_url">
                                    Вперед
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <div v-if="comments.links && comments.last_page > 1" class="pagination">
            <button
                @click="fetchComments(comments.prev_page_url)"
                :disabled="!comments.prev_page_url">
                Назад
            </button>
            <button
                @click="fetchComments(comments.next_page_url)"
                :disabled="!comments.next_page_url">
                Вперед
            </button>
        </div>
        <CommentForm 
            v-if="isCommentFormVisible"
            :commenId="commenId" 
            @close="closeForm"
            @comment-created="handleCommentCreated"
            ref="commentFormRef"
        />
    </div>
</template>
<script setup>
import {onMounted, ref, nextTick} from 'vue';
import axios from 'axios';
import CommentForm from "./CommentForm.vue";

const comments = ref({data: [],});
const currentReplies = ref({data: [], links: {}});
const sortField = ref('created_at');
const sortDirection = ref('desc');
const isCommentFormVisible = ref(false);
const commenId = ref(null);
const expandedComments = ref({});
const activeCommentId = ref(null);
const commentFormRef = ref(null);

const fetchComments = async (url = '/api/comments?page=1') => {
    try {
        const response = await axios.get(url, {
            params: {
                sortField: sortField.value,
                sortDirection: sortDirection.value
            }
        });

        comments.value = response.data;
    } catch (error) {
        console.error('Ошибка при получении комментариев:', error);
    }
};
const toggleReplies = async (commentId) => {
    closeForm()
    if (activeCommentId.value === commentId) {
        activeCommentId.value = null;
        expandedComments.value[commentId] = false;
        return;
    }

    activeCommentId.value = null;
    Object.keys(expandedComments.value).forEach((id) => {
        expandedComments.value[id] = false;
    });

    activeCommentId.value = commentId;
    expandedComments.value[commentId] = true;

    try {
        const response = await axios.get(`/api/comments/${commentId}/replies`);
        currentReplies.value = response.data;

    } catch (error) {
        console.error('Ошибка при получении ответов:', error);
    }
};
const loadRepliesPage = async (url) => {
    if (!url) return;
    try {
        const response = await axios.get(url);
        currentReplies.value = response.data;
    } catch (error) {
        console.error('Ошибка при загрузке страницы ответов:', error);
    }
};
const showCreatePostForm = () => {
    commenId.value = null;
    isCommentFormVisible.value = true;
    nextTick(() => {
        if (commentFormRef.value) {
            commentFormRef.value.$el.scrollIntoView({ behavior: 'smooth' });
        }
    });
};
const replyToComment = (commentId) => {
    commenId.value = commentId;
    isCommentFormVisible.value = true;
    nextTick(() => {
        if (commentFormRef.value) {
            commentFormRef.value.$el.scrollIntoView({ behavior: 'smooth' });
        }
    });
};
const closeForm = () => {
    isCommentFormVisible.value = false;
    commenId.value = null;
    fetchComments();
};
const handleCommentCreated = () => {
    fetchComments();
    if (activeCommentId.value) {
        toggleReplies(activeCommentId.value);
    }
};
const formatDate = (date) => {
    const options = {year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit'};
    return new Date(date).toLocaleString('ru-RU', options);
};
const sortComments = (field) => {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'desc' ? 'asc' : 'desc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
    fetchComments();
};
onMounted(() => {
    fetchComments();
});
</script>

<style scoped>
.container{
    max-width: 1200px;
    margin: 20px auto ;
}
table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 10px;
    border: 1px solid #ddd;
}

th {
    background-color: #f4f4f4;
}

.avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid transparent;
    transition: border-color 0.2s ease;
}

.avatar:hover {
    border-color: #dc3545;
}

.pagination {
    margin-top: 20px;
}

.pagination button {
    padding: 5px 10px;
    margin-right: 5px;
}


.reply-header{
    display: flex;
    align-items: center;
    gap: 10px;
    padding-left: 10px;
    background-color: #ededf0;
}
.reply-btn {
    margin:   5px 10px 5px auto;
    height: 40px;
}

.replies {
    margin-left: 20px;
    margin-top: 10px;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    width: 100%;
}

.reply {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 10px;
    margin-bottom: 10px;
    transition: all 0.3s ease;
    align-self: flex-end;
}

.reply:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.reply-header {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 10px;
    background-color: #ededf0;
    border-radius: 4px 4px 0 0;
    margin: -10px -10px 10px -10px;
}

button {
    cursor: pointer;
}

.header-btn{
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
</style>
