<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';
import { useAuthStoreDoctor } from '../../stores/AuthDoctor';

const users = ref([]);
const pagination = ref({});
const searchQuery = ref('');
const doctorId = ref(null);
const isLoading = ref(false);
const toaster = useToastr();
const permissions = ref({});

const doctors = useAuthStoreDoctor();

onMounted(async () => {
    await doctors.getDoctor();
    doctorId.value = doctors.doctorData.id;
    await getUsers();
});

const getUsers = async (page = 1) => {
    try {
        const response = await axios.get(`/api/users/receptionist?page=${page}`);
        users.value = response.data.data;
        pagination.value = response.data.meta;
        await fetchPermissions();
    } catch (error) {
        toaster.error('Failed to fetch users');
        console.error('Error fetching users:', error);
    }
};

const fetchPermissions = async () => {
    try {
        const response = await axios.get('/api/doctor-user-permissions', {
            params: { doctor_id: doctorId.value }
        });

        if (response.data.success) {
            const permissionsMap = response.data.data.reduce((acc, permission) => {
                // Convert 1/0 to true/false
                acc[permission.user_id] = Boolean(permission.is_able_to_force);
                return acc;
            }, {});

            users.value = users.value.map(user => ({
                ...user,
                is_able_to_force: Boolean(permissionsMap[user.id] ?? false),
            }));
        }
    } catch (error) {
        toaster.error('Failed to fetch permissions');
        console.error('Error fetching permissions:', error);
    }
};

const handlePermissionChange = async (userId, checked) => {
    try {
        isLoading.value = true;

        await axios.post('/api/doctor-user-permissions', {
            doctor_id: doctorId.value,
            user_id: userId,
            is_able_to_force: checked ? 1 : 0,  // Convert true/false to 1/0 for API
        });

        toaster.success(`Permission ${checked ? 'granted' : 'removed'} successfully`);

        const userIndex = users.value.findIndex(u => u.id === userId);
        if (userIndex !== -1) {
            users.value[userIndex] = {
                ...users.value[userIndex],
                is_able_to_force: checked  // Store as boolean in local state
            };
        }
    } catch (error) {
        toaster.error('Failed to update permission');
        console.error('Error updating permission:', error);

        const userIndex = users.value.findIndex(u => u.id === userId);
        if (userIndex !== -1) {
            users.value[userIndex] = {
                ...users.value[userIndex],
                is_able_to_force: !checked
            };
        }
    } finally {
        isLoading.value = false;
    }
};

const debouncedSearch = (() => {
    let timeout;
    return () => {
        clearTimeout(timeout);
        timeout = setTimeout(async () => {
            try {
                if (searchQuery.value == null || searchQuery.value.trim() === '') {
                    getUsers();
                    return;
                }
                isLoading.value = true;
                const response = await axios.get('/api/users/search', {
                    params: { query: searchQuery.value,
                        role:'receptionist'
                     },
                });
                users.value = response.data.data;
                pagination.value = response.data.meta;
                await fetchPermissions();
            } catch (error) {
                toaster.error('Failed to search users');
                console.error('Error searching users:', error);
            } finally {
                isLoading.value = false;
            }
        }, 300);
    };
})();

watch(searchQuery, debouncedSearch);
</script>

<template>
    <div class="content">
        <div class="container">
            <!-- Search Bar -->
            <div class="search-bar">
                <input type="text" class="form-control search-input" v-model="searchQuery"
                    placeholder="Search users..." />
            </div>

            <!-- Loading Indicator -->
            <div v-if="isLoading" class="loading-container">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <!-- Users Table -->
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th class="text-center">Permission</th>
                        </tr>
                    </thead>
                    <tbody v-if="users.length > 0">
                        <tr v-for="user in users" :key="user.id">
                            <td>
                                <img v-if="user.avatar" :src="user.avatar" :alt="`Photo of ${user.name}`"
                                    class="user-avatar" />
                                <span v-else class="no-photo">No Photo</span>
                            </td>
                            <td class="text-center">{{ user.name }}</td>
                            <td class="text-center">{{ user.email }}</td>
                            <td class="text-center">
                                <div class="checkbox-wrapper">
                                    <input type="checkbox" class="form-check-input text-center"  :id="'user-' + user.id"
                                        v-model="user.is_able_to_force"
                                        @change="handlePermissionChange(user.id, $event.target.checked)"
                                        :disabled="isLoading" />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="4" class="text-center no-data">No users found</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="pagination-container">
                <Bootstrap5Pagination :data="pagination" @pagination-change-page="getUsers" />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Container */
.container {
    max-width: 900px;
    margin: auto;
    padding: 20px;
}

/* Search Bar */
.search-bar {
    margin-bottom: 15px;
}

.search-input {
    padding: 8px;
    border-radius: 8px;
}

/* Loading Indicator */
.loading-container {
    text-align: center;
    margin-bottom: 15px;
}

/* Table */
.table {
    border-radius: 8px;
    overflow: hidden;
}

.table th {
    background-color: #007bff;
    color: white;
    text-align: center;
}

.table th,
.table td {
    padding: 12px;
    vertical-align: middle;
}

.no-data {
    font-weight: bold;
    color: #777;
}

/* User Avatar */
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.no-photo {
    color: #888;
    font-style: italic;
}

/* Checkbox Styling */
.checkbox-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
}

.form-check-input {
    cursor: pointer;
    transform: scale(1.2);
}

/* Pagination */
.pagination-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}
</style>
