import { defineStore } from 'pinia';
import axios from 'axios';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null, // Store the authenticated user's data
  }),

  getters: {
    // Getter to access the authenticated user's ID
    userId: (state) => state.user?.id || null,
  },

  actions: {
    // Fetch the authenticated user's data
    async fetchUser() {
      try {
        const response = await axios.get('/api/user', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`, // Add authorization header if needed
          },
        });

        if (response.data) {
          this.user = response.data; // Update the user state
        } else {
          this.user = null; // Clear user if no data is returned
        }
      } catch (error) {
        console.error('Failed to fetch user:', error);
        this.user = null; // Clear user on error
      }
    },

    // Clear the user data (e.g., on logout)
    clearUser() {
      this.user = null;
      localStorage.removeItem('token'); // Clear token from localStorage if applicable
    },
  },
});