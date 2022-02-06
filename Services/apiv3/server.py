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
from modules import communication as c

##############################
# Main Functions                 #
##############################

def handle_post(package):
    print_default("------------------------------")
    print_default("Interpretting Request...")
    final_export = p.processing(request.form)
    if final_export:
        c.transfer_to_database(final_export)

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
    return "<h1>Send only POST requests to the correct endpoint</h1>"

@app.route("/endpoint",methods = ["POST", "GET"])
def on_request():
   if request.method == "POST":
      handle_post(request)
      return "go away"
   else:
      user = request.args.get("nm")
      return "<h1>This is a POST-only API.</h1>"

##############################
# Initialization             #
##############################

if __name__ == "__main__":
   app.run(host ="0.0.0.0", port = 5000, debug = True)
