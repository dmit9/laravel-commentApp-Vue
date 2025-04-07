<template>
    <div class="captcha">
        <img :src="captchaUrl" alt="CAPTCHA" @click="refreshCaptcha">
        <input
            type="text"
            v-model="localCaptchaInput"
            placeholder="Enter the code"
            required
            @input="updateCaptcha"
        >
    </div>
</template>

<script setup>
import {ref, onMounted} from 'vue';
import axios from 'axios';
const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
});
const captchaUrl = ref('api/captcha');
const captchaToken = ref('');
const localCaptchaInput = ref(props.modelValue);
const isCaptchaLoaded = ref(false);
const emit = defineEmits(['update:modelValue']);
const updateCaptcha = () => {
    emit('update:modelValue', localCaptchaInput.value);
};
axios.interceptors.request.use((config) => {
    if (captchaToken.value) {
        config.headers['X-Captcha-Token'] = captchaToken.value;
    }
    return config;
});

const refreshCaptcha = async () => {
  //  if (isCaptchaLoaded.value) return;
    try {
        const response = await axios.get('api/captcha', { responseType: 'blob' });
        captchaUrl.value = URL.createObjectURL(response.data);
        captchaToken.value = response.headers['x-captcha-token'];
        console.log(captchaToken.value)
        isCaptchaLoaded.value = true;
    } catch (error) {
        console.error('Error loading captcha:', error);
    }
};
const resetCaptcha = () => {
    captchaUrl.value = '';
    captchaToken.value = '';
    localCaptchaInput.value = '';
    isCaptchaLoaded.value = false;
};
onMounted(refreshCaptcha);
defineExpose({ resetCaptcha });
</script>

<style scoped>
.captcha {
    display: flex;
    align-items: center;
    gap: 10px;
}

img {
    cursor: pointer;
}
</style>
