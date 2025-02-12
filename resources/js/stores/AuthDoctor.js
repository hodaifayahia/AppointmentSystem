import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export const useAuthStoreDoctor = defineStore('authDoctor', () => {
    const doctorData = ref({
        id: null,
        name: '',
        email: '',
        phone: '',
        patients_based_on_time: false,
        specialization: '',
        specialization_id: '',
        frequency: '',
        avatar: null,
        customDates: [],
        schedules: [],
        appointment_booking_window: null,
        password: '',
        number_of_patients_per_day: 0,
        time_slot: null,
    });

    const getDoctor = async () => {
        try {
            const response = await axios.get('/api/doctors/handel/specific');
            const data = response.data.data || {};

            doctorData.value = {
                id: data.id || null,
                name: data.name || '',
                email: data.email || '',
                phone: data.phone || '',
                patients_based_on_time: data.patients_based_on_time || false,
                specialization: data.specialization || '',
                specialization_id: data.specialization_id || '',
                frequency: data.frequency || '',
                avatar: data.avatar || null,
                customDates: data.customDates || [],
                schedules: data.schedules || [],
                appointment_booking_window: data.appointment_booking_window || null,
                password: '',
                number_of_patients_per_day: Array.isArray(data.schedules)
                    ? Math.max(0, ...data.schedules.map(s => s.number_of_patients_per_day ?? 0))
                    : 0, // Get the highest number_of_patients_per_day
                time_slot: data.time_slots || null,
            };
            
        } catch (error) {
            console.error('Error fetching doctor data:', error);
            doctorData.value = {
                id: null,
                name: '',
                email: '',
                phone: '',
                patients_based_on_time: false,
                specialization: '',
                specialization_id: '',
                frequency: '',
                avatar: null,
                customDates: [],
                schedules: [],
                appointmentBookingWindow: null,
                password: '',
                number_of_patients_per_day: 0,
                time_slot: null,
            };
        }
    };

    return { doctorData, getDoctor };
});
