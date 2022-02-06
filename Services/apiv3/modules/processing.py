###########################
# Main Processing Module  #
# 4/2/2021                #
# Will analyze and return #
# a transmittable object  #
###########################

###########################
# Imports                 #
###########################

import math
import cv2
import json
import pytesseract
import numpy as np

###########################
# Import Config           #
###########################

def init_config():
    with open('config.json') as f:
      return json.load(f)

config = init_config()

###########################
# Globals                 #
###########################

object_keys = [
    "name_screenshot",
    "volume_screenshot",
    "sales_array",
    "buy_array"
]

type = ""

###########################
# Main Function           #
###########################

def processing(object):
    global type
    print("------------------------------")
    print("Reloading config....")
    global config
    config = init_config()
    print("config:", config)
    print("------------------------------")
    print("Performing OCR....")
    location = object["location"]
    # select which config to use
    scales = config["processing"]["default"]["scales"]
    tolerances = config["processing"]["default"]["tolerances"]
    misc = config["processing"]["default"]["misc"]
    if location == "Citadel":
        scales = config["processing"]["citadel"]["scales"]
        tolerances = config["processing"]["citadel"]["tolerances"]
        misc = config["processing"]["citadel"]["misc"]
    elif location == "Harbor":
        scales = config["processing"]["harbor"]["scales"]
        tolerances = config["processing"]["harbor"]["tolerances"]
        misc = config["processing"]["harbor"]["misc"]
    print(scales, tolerances, misc, location)
    # extract info
    name = analyze_name(object["name_screenshot"], scales, tolerances, misc)
    volume = analyze_volume(object["volume_screenshot"], scales, tolerances, misc)
    type = "sale"
    sales = analyze_sales(object["sales_array"], scales, tolerances, misc)
    type = "buy"
    buys = analyze_buys(object["buy_array"], scales, tolerances, misc)
    name = post_process_name(name)
    print("------------------------------")
    print("Report....")
    print(name)
    print(volume)
    print(sales)
    print(buys)
    print("------------------------------")
    print("Exporting....")
    return {
        "name": name,
        "volume": volume,
        "buys": buys,
        "sales": sales,
        "location": location
    }

###########################
# Processing Functions    #
###########################

def analyze_name(object, scales, tolerances, misc):
    image = process_raw(object)
    # cv2.imwrite("out/name.png", image)
    usable = convert_json_to_usable(image, scales["name"], tolerances["name"], misc["iterations"])
    name = analyze_usable(usable, config = config["system"]["analyze_configs"]["name"])
    return name

def analyze_volume(object, scales, tolerances, misc):
    image = process_raw(object)
    # cv2.imwrite("out/volume.png", image)
    usable = convert_json_to_usable(image, scales["volume"], tolerances["volume"], misc["iterations"])
    volume = analyze_usable(usable, config = config["system"]["analyze_configs"]["volume"])
    return volume

def analyze_sales(sales, scales, tolerances, misc):
    sales_processed = []
    sales = json.loads(sales)
    for x in range(len(sales)):
        record_out = analyze_record(sales[x], scales, tolerances["white"], tolerances["gray"], tolerances["record_sale"], misc["iterations"])
        sales_processed.append(record_out)
    return sales_processed

def analyze_buys(buys, scales, tolerances, misc):
    buys_processed = []
    buys = json.loads(buys)
    for x in range(len(buys)):
        record_out = analyze_record(buys[x], scales, tolerances["white"], tolerances["gray"], tolerances["record_buy"], misc["iterations"])
        buys_processed.append(record_out)
    return buys_processed

##############################################
# TODO: hide this from the light of day      #
##############################################

def post_process_name(name):
    name = name.replace(" Ill", " III")
    name = name.replace(" IIl", " III")
    name = name.replace(" IlI", " III")
    name = name.replace(" lII", " III")
    name = name.replace(" llI", " III")
    name = name.replace(" Il", " II")
    name = name.replace(" lI", " II")
    name = name.replace(" ll", " II")
    name = name.replace(" Ik", " II")
    name = name.replace(" Ikk", " III")
    name = name.replace(" IIk", " III")
    name = name.replace(" kIk", " III")
    name = name.replace(" kkI", " III")
    name = name.replace(" Ikk", " III")
    name = name.replace(" kI", " II")
    name = name.replace(" Ik", " II")
    return name



###########################
# Record-Processing F(x)s #
###########################

def analyze_record(record, scales, white, gray, misc, iterations):
    global type
    original_image = process_raw(record)
    o_i_height = original_image.shape[0]
    o_i_width = original_image.shape[1]
    record = []
    for x in range(3):
        start_x = math.floor(x * o_i_width * (1 / 3))
        end_x = math.floor((x + 1) * o_i_width * (1 / 3))
        image = original_image[0: o_i_height, start_x: end_x]
        image = cv2.copyMakeBorder(image, 10, 10, 10, 10, cv2.BORDER_REPLICATE)
        if(x == 0):
            tolerance = misc
        elif(x == 1):
            tolerance = white
        else:
            tolerance = gray

        usable = convert_json_to_usable(image, scales["record" + str(x)], tolerance, iterations)
        record_content = analyze_usable(usable, config = config["system"]["analyze_configs"]["records"])
        record_content = record_content.replace(".", "")
        record.append(record_content)
        cv2.imwrite("out/record" + str(x) + "x" + str(type) + ".png", usable)
        print("analyzing record " + str(x) + " with tolerance of " + str(tolerance) + " and a scale of " + str(scales["record" + str(x)]) + " and found " + record_content)
    return record # separates by whitespace

###########################
# Helper Functions        #
###########################

def convert_json_to_usable(img, scale, threshold, iterations):
    img = cv2.resize(img, (math.floor(img.shape[1] * scale), math.floor(img.shape[0] * scale)))
    gray = cv2.cvtColor(img, cv2.COLOR_RGB2GRAY)
    gray = cv2.convertScaleAbs(gray)
    kernel = np.ones((2, 1), np.uint8)
    thresh, img_bin = cv2.threshold(gray, 128, 255, (cv2.THRESH_BINARY | cv2.THRESH_OTSU) + 3)
    gray = cv2.bitwise_not(img_bin)
    img = cv2.erode(gray, kernel, iterations=iterations)
    img = cv2.dilate(img, kernel, iterations=iterations)
    return img

def process_raw(raw):
    arr = np.float32(np.array(json.loads(raw)))
    cv2_format = cv2.cvtColor(arr, cv2.IMREAD_COLOR)
    return cv2_format

def analyze_usable(img, config):
    out = pytesseract.image_to_string(img, config = config)
    return out[:-2]
