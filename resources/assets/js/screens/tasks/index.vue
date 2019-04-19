<script type="text/ecmascript-6">
    import Launch from './launch.vue';

    export default {
        components: {Launch}
    }
</script>

<template>
    <div class="testtest">
        <custom-index-screen title="Tasks" resource="tasks">
            <tr slot="table-header">
                <th scope="col">Domain</th>
                <th scope="col">Repo</th>
                <th scope="col">Branch</th>
                <th scope="col">Server</th>
                <th scope="col"></th>
            </tr>

            <template slot="row" slot-scope="slotProps">
                <td class="table-fit">
                    {{ slotProps.entry.domain }}
                </td>

                <td class="table-fit">
                    {{ slotProps.entry.repo }}
                </td>

                <td class="table-fit">
                    {{ slotProps.entry.branch }}
                </td>

                <td class="table-fit">
                    {{ slotProps.entry.server }}
                </td>

                <td class="table-fit">
                    <Launch :repo="slotProps.entry.repo" :branch="slotProps.entry.branch" />
                </td>

            </template>
        </custom-index-screen>
    </div>

</template>
