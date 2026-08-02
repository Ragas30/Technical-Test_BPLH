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
    async (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem(TOKEN_KEY);
            window.dispatchEvent(new CustomEvent('docflow:unauthorized'));
        }

        if (error.response?.data instanceof Blob && error.response.data.type === 'application/json') {
            try {
                const text = await error.response.data.text();
                error.response.data = JSON.parse(text);
            } catch (e) {
                // Ignore parse failures
            }
        }

        return Promise.reject(error);
    }
);

export default http;
