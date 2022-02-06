##############################
# Main Communication Module  #
# 4/2/2021                   #
# Will analyze and return    #
# a transmittable object     #
##############################

##############################
# Imports                    #
##############################

import requests as req
import time
import numpy as np
import json

##############################
# Config                     #
##############################

target = "http://192.168.7.88/api/index.php"
discord_target = "http://192.168.7.105:42069"

##############################
# Functions                  #
##############################

def transfer_to_database(object):
    best_buy = None
    if len(object["buys"]) > 0:
        best_buy = object["buys"][0][0]
    else:
        best_buy = "--"

    best_sell = None
    if len(object["sales"]) > 0:
        best_sell = object["sales"][0][0]
    else:
        best_sell = "--"

    payload = {}
    payload["name"] = object["name"]
    payload["bestOfferBuy"] = best_buy
    payload["bestOfferSell"] = best_sell
    payload["volume"] = object["volume"]
    payload["buyRecords"] = object["buys"]
    payload["sellRecords"] = object["sales"]
    payload["location"] = object["location"]
    response = req.post(target, json = {"payload": payload})
    print("------------------------------")
    print("Debug...")
    print(payload)
    print("------------------------------")
    print("Transferring to database....")
    print(response.text)
    res_decoded = json.loads(response.text)
    print(res_decoded)
    if "response" in res_decoded:
        if res_decoded["response"] == "This reached the end of the file.":
            try:
                discord_response = req.post(discord_target, json = {"payload": payload})
            except req.exceptions.HTTPError as errh:
                print("An Http Error occurred:" + repr(errh))
            except req.exceptions.ConnectionError as errc:
                print("An Error Connecting to the API occurred:" + repr(errc))
            except req.exceptions.Timeout as errt:
                print("A Timeout Error occurred:" + repr(errt))
            except req.exceptions.RequestException as err:
                print("An Unknown Error occurred" + repr(err))