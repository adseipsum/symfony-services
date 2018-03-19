#!/usr/bin/env bash
http -f POST http://localhost:8000/oauth/v2/token \
    grant_type=password \
    client_id=1_g7fxszqcapkw84048o4kg4w8oc0800ccg80kko48ws0k44wow \
    client_secret=4ibwinsr1400cgcwggg88wookccwsoocckkcwcg40gc84socs4 \
    redirect_uri=https://api.aitext.me/oauth/oauth-callback \
    username=testuser \
    password=abcdef
