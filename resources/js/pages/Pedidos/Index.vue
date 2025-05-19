<script setup lang="ts">
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';


const breadcrumbs: BreadcrumbItem[] = [{title: 'Pedidos', href: '/pedidos'}];
</script>

<template>
   <Head title="Pedidos" />

    <AppLayout :breadcrumbs="breadcrumbs">


    </AppLayout>

</template>

