
Hi, we are still working on the project's code so I warn you not to use it in production. Any feedback or help is very appreceated!

Quick learning video is on the way!

# Installation
Clone project to your CI machine.
```bash
git clone https://github.com/michaelradionov/pullkins ;\
cp .env.example .env
```

- Edit .env to set database, admin panel and Jira (if needed) credentials
- Create and copy/paster your Slack webhook URLs;
- Edit Caddyfile_production to set auto-SSL. All you need is to replace `0.0.0.0:80` with your domain like `site.ru`

 Then spin up docker-compose app like this
```bash
docker-compose up -d ;\
docker-compose exec workspace make init; \
chmod -R 777 storage bootstrap
```
If you running clean Ubuntu 16.04, then you can install Docker and Docker Compose using my snippet
```bash
curl https://gist.githubusercontent.com/michaelradionov/84879dc686e7f9e43bc38ecbbd879af4/raw/17f942d078b5b2202dd12eab9a5c4d55b4a06259/Docker_Ubuntu_16.sh | sudo bash
```

# Usage

If you are using Bitbucket navigate to your repo's settings and create webhook with URL `http://your-pullkins-ip-or-domain.com/webhooks/bitbucket`.

I recommend you also clone Pullkins to your local computer and from there edit config files in `/pullkins` folder. First you can create task for pulling Pullkins itself, then you can just push you new configs from you local machine!

## Pullkins configs

1. Edit config/pullkins.php (for slack channels);
2. Create server in pullkins/servers.yml
3. Create task by copying pullkins/taks/site.yml

## Config variables

- ${branch} - Branch from webhook
- ${repo} - Repository from webhook

## SSH RSA keys

Don't forget to give Pullkins server access to your other target servers.
1. First you need to create RSA SSH key on Pullkins server by running `ssh-keygen` and hitting enter three times.
2. Then you should copy `~/.ssh/id_rsa.pub` content.
3. Then you should paste (append) it to target servers's `~/.ssh/authorized_keys` file like this

```bash
echo "copied public key" >> ~/.ssh/authorized_keys
```

# Roadmap
- Get rid of the Telescope (Thanks Mohamed Said for your work! 🙏)
- Rollbacks or/and Docker images shipment
- Github, Gitlab support
- Telegram support
