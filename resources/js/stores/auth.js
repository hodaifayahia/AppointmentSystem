import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref({
        name: '',
        role: '',
        email: '',
        avatar: '',
        phone: '',
    });
    const isLoading = ref(true);

    const getUser = async () => {
        try {
            const response = await axios.get('/api/setting/user');
            user.value = response.data.data;
            isLoading.value = false;
        } catch (error) {
            if (error.response && error.response.status === 401) {
                user.value = { name: '', role: '', email: '', avatar: '', phone: '' };
                isLoading.value = false;
                throw error; // Throw the error to be handled by the router.beforeEach guard
            }
            isLoading.value = false;
        }
    };

    return { user, isLoading, getUser };
});
