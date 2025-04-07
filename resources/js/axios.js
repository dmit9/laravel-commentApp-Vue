import axios from 'axios';

const instance = axios.create({
    baseURL: 'https://comment.prototypecodetest.site',
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});

export default instance; 