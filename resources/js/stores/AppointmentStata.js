import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useAppointmentStore = defineStore('appointment', () => {
    const appointments = ref([]);
    const pagination = ref({});
    const loading = ref(false);
    const error = ref(null);

    const getAppointments = async (doctorId, page = 1, status = 0, filter = null, date = null) => {
        loading.value = true;
        error.value = null;

        try {
            const params = { page, status, filter, date };
            const { data } = await axios.get(`/api/appointments/${doctorId}`, { params });

            if (data.success) {
                appointments.value = data.data;
                pagination.value = data.meta;
            }
        } catch (err) {
            console.error('Error fetching appointments:', err);
            error.value = 'Failed to load appointments. Please try again.';
        } finally {
            loading.value = false;
        }
    };

    return { appointments, pagination, loading, error, getAppointments };
});
