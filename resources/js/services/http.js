import axios from 'axios';

export const TOKEN_KEY = 'docflow_token';

const http = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

http.interceptors.request.use((config) => {
    const token = localStorage.getItem(TOKEN_KEY);

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem(TOKEN_KEY);
            window.dispatchEvent(new CustomEvent('docflow:unauthorized'));
        }

        return Promise.reject(error);
    }
);

export default http;
