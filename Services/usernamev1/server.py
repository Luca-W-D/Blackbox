##############################
# Main Server File           #
# 4/6/2021                   #
# Intakes and routes all     #
# inbound requests.          #
##############################

##############################
# Imports                    #
##############################

from flask import Flask, request, render_template, url_for, redirect
import sys
import cv2
import numpy as np
import json
from modules import processing as p

##############################
# Main Functions                 #
##############################

def handle_post(package):
    print_default("------------------------------")
    print_default("Interpretting Request...")
    return json.dumps(p.processing(request.form))


##############################
# Helpers                 #
##############################

def print_default(string):
    print(string, file=sys.stdout)

def print_error(string):
    print(string, file=sys.stderr)

##############################
# Flask Prep                 #
##############################

app = Flask(__name__)

@app.route("/",methods = ["POST", "GET"])
def wrong_endpoint():
    return "<h1>Please send a POST request to the correct endpoint.</h1>"

@app.route("/endpoint",methods = ["POST", "GET"])
def on_request():
   # request.decode('UTF-8')
   if request.method == "POST":
      return handle_post(request)
   else:
      return "<h1>Please send only a POST request.</h1>"

##############################
# Initialization             #
##############################

if __name__ == "__main__":
   app.run(host ="0.0.0.0", port = 5001, debug = True)
