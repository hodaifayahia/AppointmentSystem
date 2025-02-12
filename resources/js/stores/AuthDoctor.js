import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';
export const useAuthStoreDoctor = defineStore('auth', () => {
    const user = ref({
        id: '',
        role: '',
        specialization_id: '',
    });

    const getDoctor = async () => {
        try {
            const response = await axios.get('/api/role');
            user.value = response.data;
        } catch (error) {
            if (error.response?.status === 401) {
                user.value = { id: '', role: '', specialization_id: '' };
                throw error;
            }
        }
    };

    return { user, getDoctor };  // Ensure `getDoctor` is being returned
});
