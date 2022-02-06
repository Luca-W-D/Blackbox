###########################
# Main Processing Module  #
# 4/2/2021                #
# Will analyze and return #
# a transmittable object  #
###########################

###########################
# Imports                 #
###########################

import json
import requests

###########################
# Main Function           #
###########################

def processing(obj):
    user_id = obj["user_id"]
    response = requests.get("https://users.roblox.com/v1/users/" + user_id)
    response = json.loads(response.text)
    if "name" in response:
        username = response["name"]
    else:
        username = user_id
    return {"username": username}
