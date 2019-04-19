<template>
    <button title="Launch" class="btn btn-launch" :class="{'btn-secondary': sending, 'btn-outline-primary': !sending && !success && !error, 'btn-danger': error, 'btn-success': success}" @click="launch" :disabled="sending || success || error">
        {{ text }}
    </button>
</template>

<script>
    import axios from 'axios';

    export default {
        name: 'Launch',
        props: {
            'repo': {
                default: null,
            },
            'branch': {
                default: null,
            }
        },
        data() {
            return {
                sendStatus: 'READY',
                text: 'Launch',
            }
        },
        computed: {
            sending() {
                return this.sendStatus === 'SENDING';
            },
            success() {
                return this.sendStatus === 'SUCCESS';
            },
            error() {
                return this.sendStatus === 'ERROR';
            }
        },
        methods: {
            unblock() {
                setTimeout(() => {
                    this.sendStatus = 'READY';
                    this.text = 'Launch';
                }, 2000);
            },
            launch() {
                this.sendStatus = 'SENDING';
                this.text = 'Waiting';

                axios.post('/tasks/execute', {
                    repo: this.repo,
                    branch: this.branch
                })
                .then(res => {
                    const code = res.status;

                    this.sendStatus = 'SUCCESS';
                    this.text = code;
                    this.unblock();
                })
                .catch(err => {
                    const code = err.response.status;

                    console.log(err);
                    this.sendStatus = 'ERROR';
                    this.text = code;
                    this.unblock();
                });
            }
        }
    }
</script>