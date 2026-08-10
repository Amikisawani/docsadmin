<template>
    <div>
        <div class="card">
            <DataTable
                v-model:filters="filters"
                :value="archiveBoxes"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 20, 50]"
                :loading="loading"
                dataKey="id"
                filterDisplay="row"
                class="p-datatable-sm"
            >
                <template #header>
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold">Boîtes d'Archives</h2>
                        <div class="flex items-center space-x-2">
                            <InputText v-model="globalFilter" placeholder="Recherche globale..." />
                            <Button label="Nouvelle Boîte" icon="pi pi-plus" class="p-button-success" @click="openNewBoxModal" />
                        </div>
                    </div>
                </template>

                <template #empty> Aucune boîte d'archive trouvée. </template>
                <template #loading> Chargement des données... </template>

                <Column field="code" header="Code" :sortable="true" style="min-width: 12rem">
                    <template #body="{ data }">
                        <span class="font-semibold">{{ data.code }}</span>
                    </template>
                </Column>

                <Column field="name" header="Nom" :sortable="true" style="min-width: 20rem">
                    <template #body="{ data }">
                        {{ data.name }}
                    </template>
                </Column>

                <Column field="location" header="Emplacement" :sortable="true" style="min-width: 14rem" />

                <Column field="status" header="Statut" :sortable="true" style="min-width: 10rem">
                     <template #body="{ data }">
                        <Tag :value="data.status" :severity="getStatusSeverity(data.status)" />
                    </template>
                </Column>

                <Column field="archives_count" header="Contenu" :sortable="true" style="min-width: 10rem">
                    <template #body="{ data }">
                        {{ data.archives_count }} / {{ data.capacity || '∞' }}
                    </template>
                </Column>

                <Column header="Actions" :exportable="false" style="min-width: 8rem">
                    <template #body="{ data }">
                        <div class="flex space-x-2">
                            <Button icon="pi pi-eye" class="p-button-rounded p-button-info" @click="viewBox(data)" />
                            <Button icon="pi pi-pencil" class="p-button-rounded p-button-warning" @click="editBox(data)" />
                            <Button icon="pi pi-trash" class="p-button-rounded p-button-danger" @click="confirmDeleteBox(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';
import api from '@/api'; // Assumant un client axios pré-configuré

interface ArchiveBox {
    id: string;
    code: string;
    name: string;
    location: string;
    status: string;
    capacity: number | null;
    archives_count: number;
}

const archiveBoxes = ref<ArchiveBox[]>([]);
const loading = ref(true);
const filters = ref({}); // Pour les filtres par colonne
const globalFilter = ref(''); // Pour la recherche globale

const fetchArchiveBoxes = async () => {
    loading.value = true;
    try {
        const response = await api.get('/api/v1/archive-boxes');
        archiveBoxes.value = response.data.data;
    } catch (error) {
        console.error("Erreur lors de la récupération des boîtes d'archives:", error);
        // TODO: Afficher une notification d'erreur à l'utilisateur
    } finally {
        loading.value = false;
    }
};

const getStatusSeverity = (status: string) => {
    if (status === 'full') return 'danger';
    if (status === 'active') return 'success';
    return 'info';
};

onMounted(fetchArchiveBoxes);

// TODO: Implémenter les fonctions openNewBoxModal, viewBox, editBox, confirmDeleteBox
</script>