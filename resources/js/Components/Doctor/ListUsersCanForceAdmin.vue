<script setup>
import { ref, onMounted, watch } from 'vue';
import axios from 'axios';
import { useToastr } from '../../Components/toster';
import { Bootstrap5Pagination } from 'laravel-vue-pagination';

const users = ref([]);
const doctorsList = ref([]);
const pagination = ref({});
const searchQuery = ref('');
const isLoading = ref(false);
const toaster = useToastr();

// Initialize users with doctorPermissions
const initializeUserPermissions = (user) => ({
    ...user,
    doctorPermissions: doctorsList.value.reduce((acc, doctor) => {
        acc[doctor.id] = {
            ...doctor,
            is_able_to_force: false
        };
        return acc;
    }, {})
});

onMounted(async () => {
    isLoading.value = true;
    try {
        // First get doctors list
        await getDoctorsList();
        // Then get users with permissions
        await getUsers();
    } catch (error) {
        console.error('Initialization error:', error);
    } finally {
        isLoading.value = false;
    }
});

const getDoctorsList = async () => {
    try {
        const response = await axios.get('/api/doctors');
        if (response.data?.data) {
            doctorsList.value = response.data.data;
        }
    } catch (error) {
        toaster.error('Failed to fetch doctors list');
        console.error('Error fetching doctors:', error);
        doctorsList.value = [];
    }
};

const getUsers = async (page = 1) => {
    try {
        const response = await axios.get(`/api/users/receptionist?page=${page}`);
        if (response.data?.data) {
            // Initialize users with empty permissions
            users.value = response.data.data.map(initializeUserPermissions);
            pagination.value = response.data.meta;
            // Then fetch actual permissions
            await fetchPermissions();
        }
    } catch (error) {
        toaster.error('Failed to fetch users');
        console.error('Error fetching users:', error);
        users.value = [];
    }
};
const fetchPermissions = async () => {
    if (!users.value.length || !doctorsList.value.length) return;

    try {
        const response = await axios.get('/api/doctor-user-permissions', {
            params: { doctor_id: null } // Assuming you have a selectedDoctorId or similar variable
        });
        
        if (response.data?.success && response.data?.data) {
            // Create permissions map
            const permissionsMap = {};
            response.data.data.forEach(permission => {
                if (!permissionsMap[permission.user_id]) {
                    permissionsMap[permission.user_id] = {};
                }
                permissionsMap[permission.user_id][permission.doctor_id] = {
                    is_able_to_force: Boolean(permission.is_able_to_force)
                };
            });

            // Update users with permissions
            users.value = users.value.map(user => ({
                ...user,
                doctorPermissions: doctorsList.value.reduce((acc, doctor) => {
                    acc[doctor.id] = {
                        ...doctor,
                        is_able_to_force: Boolean(permissionsMap[user.id]?.[doctor.id]?.is_able_to_force)
                    };
                    return acc;
                }, {})
            }));
        }
    } catch (error) {
        toaster.error('Failed to fetch permissions');
        console.error('Error fetching permissions:', error);
    }
};

const handlePermissionChange = async (userId, doctorId, checked) => {
    if (!userId || !doctorId) return;

    const originalState = users.value.find(u => u.id === userId)?.doctorPermissions[doctorId]?.is_able_to_force;
    
    try {
        isLoading.value = true;
        await axios.post('/api/doctor-user-permissions', {
            doctor_id: doctorId,
            user_id: userId,
            is_able_to_force: checked ? 1 : 0,
        });

        // Update local state
        const userIndex = users.value.findIndex(u => u.id === userId);
        if (userIndex !== -1 && users.value[userIndex].doctorPermissions?.[doctorId]) {
            users.value[userIndex].doctorPermissions[doctorId].is_able_to_force = checked;
            toaster.success(`Permission ${checked ? 'granted' : 'removed'} successfully`);
        }
    } catch (error) {
        toaster.error('Failed to update permission');
        console.error('Error updating permission:', error);
        
        // Revert to original state
        const userIndex = users.value.findIndex(u => u.id === userId);
        if (userIndex !== -1 && users.value[userIndex].doctorPermissions?.[doctorId]) {
            users.value[userIndex].doctorPermissions[doctorId].is_able_to_force = originalState;
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
                if (!searchQuery.value?.trim()) {
                    getUsers();
                    return;
                }
                isLoading.value = true;
                const response = await axios.get('/api/users/search', {
                    params: {
                        query: searchQuery.value,
                        role: 'receptionist'
                    },
                });
                if (response.data?.data) {
                    users.value = response.data.data.map(initializeUserPermissions);
                    pagination.value = response.data.meta;
                    await fetchPermissions();
                }
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
        <input 
          type="text" 
          class="form-control search-input" 
          v-model="searchQuery"
          placeholder="Search users..." 
          :disabled="isLoading"
          @keyup.enter="getUsers"
        />
      </div>
      <!-- Loading Indicator -->
      <div v-if="isLoading" class="loading-container">
        <div class="spinner-border text-primary" role="status">
        </div>
      </div>
      <!-- Users Table -->
      <div v-else class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Image</th>
              <th>Name</th>
              <th>Username</th>
              <th class="text-center">Doctor Permissions</th>
            </tr>
          </thead>
          <tbody v-if="users.length > 0">
            <tr v-for="user in users" :key="user.id">
              <td>
                <img 
                  v-if="user.avatar" 
                  :src="user.avatar" 
                  style="width: 40px; height: 40px;" 
                  :alt="`Photo of ${user.name}`"
                  class="user-avatar" 
                />
                <span v-else class="no-photo">No Photo</span>
              </td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>
                <div v-if="doctorsList.length" class="doctors-permissions-container">
                  <div 
                    v-for="doctor in doctorsList" 
                    :key="doctor.id" 
                    class="doctor-permission-item"
                  >
                    <label class="doctor-info">
                      <span class="doctor-name">{{ doctor.name }}</span>
                      <div class="custom-checkbox">
                        <input 
                          type="checkbox" 
                          class="form-check-input"
                          :checked="user.doctorPermissions?.[doctor.id]?.is_able_to_force"
                          @change="handlePermissionChange(user.id, doctor.id, $event.target.checked)"
                          :disabled="isLoading"
                        />
                        <span class="checkmark"></span>
                      </div>
                    </label>
                  </div>
                </div>
                <div v-else class="text-center text-muted">
                  No doctors available
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
      <div v-if="users.length > 0" class="pagination-container">
        <Bootstrap5Pagination 
          :data="pagination" 
          @pagination-change-page="getUsers" 
        />
      </div>
    </div>
  </div>
</template>
<style scoped>
.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

.search-bar {
    margin-bottom: 15px;
}

.search-input {
    padding: 8px;
    border-radius: 8px;
}

.loading-container {
    text-align: center;
    margin-bottom: 15px;
}

.table {
    border-radius: 8px;
    overflow: hidden;
}

.table th {
    background-color: #007bff;
    color: white;
    padding: 12px;
}

.user-avatar {
    border-radius: 50%;
    object-fit: cover;
}

.no-photo {
    color: #888;
    font-style: italic;
}
.doctors-permissions-container {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 8px;
}
.doctor-permission-item {
    flex-grow: 1;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    padding: 8px;
    background-color: #f8f9fa;
    min-width: 100px;
    max-width: 150px;
}
.doctor-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
}
.doctor-name {
    font-size: 0.9em;
    color: #333;
    flex-grow: 1;
}
.custom-checkbox {
    position: relative;
    display: inline-block;
    width: 24px;
    height: 24px;
}
.form-check-input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}
.checkmark {
    position: absolute;
    top: 0;
    left: 0;
    height: 24px;
    width: 24px;
    background-color: #eee;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.form-check-input:checked ~ .checkmark {
    background-color: #007bff;
    border-color: #007bff;
}
.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}
.form-check-input:checked ~ .checkmark:after {
    display: block;
}
.checkmark:after {
    left: 9px;
    top: 5px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
</style>