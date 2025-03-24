<template>
    <form @submit.prevent="submitComment" class="comment-form">
        <div v-if="form.errors.user" class="error-message">{{ form.errors.user }}</div>

        <input
            type="text"
            v-model="form.name"
            placeholder="Ваше имя"
            required
            pattern="^[a-zA-Z0-9]+$"
            title="Только латинские буквы и цифры"
        />
        <div v-if="form.errors.name" class="error-message">{{ form.errors.name }}</div>

        <input
            type="email"
            v-model="form.email"
            placeholder="Ваш email"
            required
        />
        <div v-if="form.errors.email" class="error-message">{{ form.errors.email }}</div>

        <input
            type="url"
            v-model="form.homepage"
            placeholder="Ваш сайт (необязательно)"
        />
        <div v-if="form.errors.homepage" class="error-message">{{ form.errors.homepage }}</div>

        <div class="image-upload">
            <input
                type="file"
                @change="handleImageUpload"
                accept=".jpg,.jpeg,.png,.gif"
                ref="fileInput"
            />
            <div class="image-requirements">
                Допустимые форматы: JPG, GIF, PNG. Максимальный размер: 320x240
            </div>
            <div v-if="form.errors.image" class="error-message">{{ form.errors.image }}</div>
            <img v-if="imagePreview" :src="imagePreview" class="image-preview" />
        </div>

        <div class="toolbar">
            <button type="button" @click="insertTag('i')">[i]</button>
            <button type="button" @click="insertTag('strong')">[strong]</button>
            <button type="button" @click="insertTag('code')">[code]</button>
            <button type="button" @click="insertLink">[a]</button>
        </div>

        <textarea
            v-model="form.text"
            :placeholder="props.commenId ? 'Текст комментария' : 'Текст поста'"
            required
        ></textarea>
        <div v-if="form.errors.text" class="error-message">{{ form.errors.text }}</div>

        <Captcha ref="captchaRef" v-model="form.captcha" />
        <div v-if="form.errors.captcha" class="error-message">{{ form.errors.captcha }}</div>

        <button type="submit">{{ props.commenId ? 'Отправить' : 'Создать пост' }}</button>
        <button @click="closeForm()">Закрыть</button>
    </form>
</template>
<script setup>
import { ref, nextTick } from 'vue';
import axios from 'axios';
import Captcha from './Captcha.vue';
const props = defineProps({
    commenId: {
        type: Number,
        required: false,
        default: null
    }
});
const form = ref({
    name: '',
    email: '',
    homepage: '',
    text: '',
    captcha: '',
    image: null,
    errors: {
        user: '',
        name: '',
        email: '',
        homepage: '',
        text: '',
        captcha: '',
        image: ''
    }
});
const emit = defineEmits(['close', 'comment-created']);
const captchaRef = ref(null);
const imagePreview = ref('');
const fileInput = ref(null);

const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        form.value.errors.image = 'Допустимы только форматы JPG, PNG и GIF';
        fileInput.value.value = ''; // Очищаем input
        imagePreview.value = '';
        return;
    }

    const img = new Image();
    const reader = new FileReader();
    reader.onload = () => {
        img.src = reader.result;
        img.onload = () => {
            const canvas = document.createElement('canvas');
            let width = img.width;
            let height = img.height;

            if (width > 320 || height > 240) {
                const ratio = Math.min(320 / width, 240 / height);
                width *= ratio;
                height *= ratio;
            }

            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob((blob) => {
                const resizedFile = new File([blob], file.name, {
                    type: file.type,
                    lastModified: new Date().getTime()
                });
                form.value.image = resizedFile;
                imagePreview.value = canvas.toDataURL();
                form.value.errors.image = '';
            }, file.type);
        };
    };
    reader.readAsDataURL(file);
};

const submitComment = async () => {
    try {
        Object.keys(form.value.errors).forEach(key => {
            form.value.errors[key] = '';
        });

        const formData = new FormData();
        Object.keys(form.value).forEach(key => {
            if (key !== 'errors') {
                if (key === 'image' && form.value[key]) {
                    formData.append('image', form.value[key]);
                } else {
                    formData.append(key, form.value[key]);
                }
            }
        });

        const url = props.commenId
            ? `/api/comments/${props.commenId}/reply`
            : '/api/comments';

        const response = await axios.post(url, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });

        emit('comment-created', response.data.comment);
        closeForm();
    } catch (error) {
        if (error.response?.status === 422 && error.response.data.errors) {
            const validationErrors = error.response.data.errors;
            Object.keys(validationErrors).forEach(key => {
                if (Array.isArray(validationErrors[key])) {
                    form.value.errors[key] = validationErrors[key][0];
                } else {
                    form.value.errors[key] = validationErrors[key];
                }
            });
        } else {
            console.error(error);
            alert(props.commenId ? 'Ошибка отправки комментария.' : 'Ошибка создания поста.');
        }
    }
};
const closeForm = () => {
    if (captchaRef.value) {
        captchaRef.value.resetCaptcha();
    }
    emit('close');
};
const insertTag = (tag) => {
    const textarea = document.querySelector('textarea');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;

    form.value.text =
        form.value.text.substring(0, start) +
        `<${tag}>${form.value.text.substring(start, end)}</${tag}>` +
        form.value.text.substring(end);

    nextTick(() => {
        textarea.focus();
        textarea.setSelectionRange(end + tag.length * 2 + 5, end + tag.length * 2 + 5);
    });
};
const insertLink = () => {
    const url = prompt('Введите URL:');
    if (!url) return;

    const textarea = document.querySelector('textarea');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;

    form.value.text =
        form.value.text.substring(0, start) +
        `<a href="${url}" title="Ссылка">${form.value.text.substring(start, end)}</a>` +
        form.value.text.substring(end);

    // Переместить курсор после вставленной ссылки
    nextTick(() => {
        textarea.focus();
        textarea.setSelectionRange(end + url.length + 28, end + url.length + 28);
    });
};


</script>

<style scoped>
.error-message {
    color: #dc3545;
    font-size: 0.875rem;
    margin: 5px 0;
    padding: 8px;
    border-radius: 4px;
    background-color: rgba(220, 53, 69, 0.1);
}

.error-message:empty {
    display: none;
}

form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 10px;
}

button {
    cursor: pointer;
}

.image-upload {
    margin: 10px 0;
}

.image-requirements {
    font-size: 0.8rem;
    color: #666;
    margin: 5px 0;
}

.image-preview {
    max-width: 320px;
    max-height: 240px;
    margin-top: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>
