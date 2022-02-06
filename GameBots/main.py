############################
# Luca D.                  #
# 2-5-2022                 #
# In-game bot rewrite      #
#                          #
# Shoutout to Alex, he's a #
# pretty cool dude...      #
############################

############################
# Imports                  #
############################

import pyautogui
import requests
from modules import movement as pydirectinput
from modules import communication as c
import cv2
import numpy as np
import pytesseract
import json
import time
import math
import random
import re
import ctypes

############################
# Annoying Windows Config  #
############################

GetDC = ctypes.windll.user32.GetDC
ReleaseDC = ctypes.windll.user32.ReleaseDC
GetDC.argtypes = [ctypes.c_ssize_t]
GetDC.restype = ctypes.c_ssize_t
ReleaseDC.argtypes = [ctypes.c_ssize_t, ctypes.c_ssize_t]
ReleaseDC.restype = ctypes.c_int
hdc = GetDC(0)
assert ReleaseDC(0, hdc)

############################
# Config                   #
############################

location = "c1" # name of bot
max_rows = 8
table_scale = 2 # Upscale for orders
row_height = 17
QUEUE_URL = "http://192.168.7.105:1024"

############################
# Globals                  #
############################

config = None
coordinates = None
item_list = None

############################
# Config                   #
############################

with open('config1080p.json') as f:
  config = json.load(f)
  coordinates = config["coordinates"]

with open('itemList.json') as f:
  item_list = json.load(f)["items"]

# default windows location
pytesseract.pytesseract.tesseract_cmd = r"C:\Program Files\Tesseract-OCR\tesseract"

############################
# Main                     #
############################

def main():
    time.sleep(0.5)
    gui_type = "sell"
    print("Starting...")
    # ensure window is actually focused
    click_at_location_name("first_row")
    while True:
        print("row:", coordinates["first_row"])
        item = getNextItemInQueue()
        focusItemInList(item)
        needs_to_check_buy = False
        if validate_item_exists(0):
            needs_to_check_buy = scanList(item)
        else:
            print("does not exist")
            needs_to_check_buy = True
        if(needs_to_check_buy):
            closeItem()
            click_at_location_name("switch_to_buy")
            focusItemInList(item)
            if validate_item_exists(0):
                scanList(item)
            click_at_location_name("switch_to_sell")
        
   

############################
# Helpers                  #
############################Red Narcor

def scanList(item):
    offset = 0
    row_count = 0
    while validate_item_exists(offset) and row_count <= max_rows:
        openXItemInList(row_count)
        waitForItemLoad("sell")
        payload = scanItem(item)
        closeItem()
        if payload != {}:
            c.transfer_to_database(payload)
            if(payload["name"].lower() == item.lower()):
                return False
            else:
                print(payload["name"], item.lower())
        row_count += 1
        offset += row_height
    return True

def openXItemInList(x):
    click_at_location_name_with_offet("first_row", x * row_height)
    click_at_location_name("open_item")

def validate_item_exists(offset):
    coords = coordinates["first_row"]
    xy = [int(coords[0]), int(coords[1])]
    xy[1] += offset
    return validate_consistent_color_at_coords(xy, 40)

def focusItemInList(name):
    click_at_location_name("search")
    pyautogui.write(name)

def openTopItemInList():
    click_at_location_name("first_row")
    click_at_location_name("open_item")

def closeItem():
    click_at_location_name("close_item")

def scanItem(current_item):
    intake_sell_array = take_screenshot_of_table("sell")
    intake_buy_array = take_screenshot_of_table("buy")
    sells_analyze = analyzeItem(intake_sell_array)
    buys_analyze = analyzeItem(intake_buy_array)
    print(sells_analyze)
    print(buys_analyze)
    sells = convertToOrders(sells_analyze)
    buys = convertToOrders(buys_analyze)
    volume = findVolume()
    name = findName()
    if sells == [] and buys == []:
        print("An order could not be processed")
        return {}
    payload = {
        "name": name,
        "volume": volume, 
        "buys": buys,
        "sales":  sells,
        "location": location
    }
    pretty(payload)
    return payload

def analyzeItem(raw_img):
    # img = processImage(item) # lmao who needs post processing... right guys?? right???
    refined_img = preprocess_img(raw_img)
    # It is recommended that you keep PSM 6 and the current charlist but it is not required
    return (pytesseract.image_to_string(refined_img, config = "-l eng --oem 1 --psm 6 digits -c tessedit_char_whitelist=\'1234567890.,\'")[:-2]).replace("\n\n", "\n")

def waitForItemLoad(type):
    counter = 0
    count = 0
    time.sleep(0.001)
    while counter < 30:
        if count > 5: return True
        if type == "sell":
            if validate_color_at_coords(coordinates["first_sell_row"], 32): count += 1
        else:
            if validate_color_at_coords(coordinates["first_buy_row"], 32): count += 1
        counter += 1
        time.sleep(0.1)

def findVolume():
    screenshot = take_screenshot_of_region("volume")
    volume = analyzeVolume(screenshot)
    return volume

def analyzeVolume(screenshot):
    img = preprocess_img(screenshot)
    out = pytesseract.image_to_string(img, config = "-l eng --oem 1 --psm 11 -c tessedit_char_whitelist=\'1234567890/kKmM.\'")[:-2]
    return out

def findName():
    screenshot = take_screenshot_of_region("name")
    name = analyzeName(screenshot)
    name = post_process_name(name)
    return name

def analyzeName(screenshot):
    img = processImageForName(screenshot)
    out = pytesseract.image_to_string(img, config = "-l eng --oem 1 --psm 11 starscapenames -c tessedit_char_whitelist=\'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890[/] '")[:-2]
    return out

def getNextItemInQueue():
    r = requests.post(QUEUE_URL, data={})
    return r.text


############################
# Basic Helpers            #
############################

def take_screenshot_of_table(table):
    if(table == "sell"):
        return np.array(pyautogui.screenshot(region=[802, 372, 303, 199]))
    else:
        return np.array(pyautogui.screenshot(region=[802, 601, 303, 199]))    

def convertToOrders(string):
    orders_arr = string.split("\n")
    final_arr = []
    for x in range(len(orders_arr)):
        arr_str = orders_arr[x]
        potential_orders = arr_str.split(" ", 2)
        if len(potential_orders) == 3:
            final_arr.append(potential_orders)
    return final_arr

def preprocess_img(raw_img):
    # Scale the image using the global "scale"
    scaled_img = cv2.resize(raw_img, (math.floor(raw_img.shape[1] * table_scale), math.floor(raw_img.shape[0] * table_scale)))
    # Converting to gray scale
    gray_img = cv2.cvtColor(scaled_img, cv2.COLOR_BGR2GRAY)
    gray_img = cv2.convertScaleAbs(gray_img)
    # Treshold transformation
    thresh, img_bin = cv2.threshold(gray_img, 128, 255, (cv2.THRESH_BINARY | cv2.THRESH_OTSU) + 2)
    threshold_img = cv2.bitwise_not(img_bin)
    # Erode / Dilation
    kernel = np.ones((2, 1), np.uint8)
    eroded_img = cv2.erode(threshold_img, kernel, iterations=1)
    dilated_img = cv2.dilate(eroded_img, kernel, iterations=1)
    # Returning whatever img you'd like
    return dilated_img

def processImageForName(img):
    scale = 3
    img = cv2.resize(img, (math.floor(img.shape[1] * scale), math.floor(img.shape[0] * scale)))
    gray = cv2.cvtColor(img, cv2.COLOR_BGR2GRAY)
    gray = cv2.convertScaleAbs(gray)
    kernel = np.ones((2, 1), np.uint8)
    thresh, img_bin = cv2.threshold(gray, 150, 255, (cv2.THRESH_BINARY | cv2.THRESH_OTSU) + 3)
    gray = cv2.bitwise_not(img_bin)
    img = cv2.erode(gray, kernel, iterations=1)
    img = cv2.dilate(img, kernel, iterations=1)
    # debug_image(img, "name")
    return img

def jiggle():
    pydirectinput.move(0, 1)
    pydirectinput.move(0, -1)

def click_at(x, y):
    pydirectinput.moveTo(x, y)
    jiggle()
    pydirectinput.click()
    jiggle()
    pydirectinput.click()

def click_once_at(x, y):
    pydirectinput.moveTo(x, y)
    jiggle()
    pydirectinput.click()

def click_at_location_name(name):
    x_y = coordinates[name]
    x = x_y[0]
    y = x_y[1]
    click_once_at(x, y)

def click_at_location_name_with_offet(name, offset):
    x_y = coordinates[name]
    x = x_y[0]
    y = x_y[1] + offset
    click_once_at(x, y)

def take_screenshot_of_region(region):
    region_bounds = coordinates[region]
    usable_bounds = raw_region_to_usable_region(region_bounds)
    return take_photo_with_predefined_coords(usable_bounds)

def take_photo_with_predefined_coords(coords):
    img = pyautogui.screenshot(region=coords)
    np_array = np.array(img)
    return np_array

def raw_region_to_usable_region(old):
    diff_x = old[2] - old[0]
    diff_y = old[3] - old[1]
    return([old[0], old[1], diff_x, diff_y])

def debug_image(array, name):
    cv2_format = cv2.cvtColor(array, cv2.COLOR_RGBA2BGR)
    cv2.imwrite(str(name) + ".png", cv2_format)

def validate_color_at_coords(coordinates, brightness):
    pixel = get_color_at_pixel(coordinates)
    if pixel[0] > brightness or pixel[1] > brightness or pixel[2] > brightness:
        return True
    else:
        return False

def get_color_at_pixel(coordinates):
    try:
        pixel = pyautogui.pixel(coordinates[0], coordinates[1])
    except: 
        pixel = pyautogui.pixel(coordinates[0], coordinates[1])
    return pixel

def validate_consistent_color_at_coords(coordinates, brightness, min = 8):
    counter = 0
    hits = 0
    last_pixel = get_color_at_pixel(coordinates)
    while counter < 30:
        print(last_pixel)
        current_pixel = get_color_at_pixel(coordinates)
        if last_pixel != current_pixel: 
            hits = 0
            last_pixel = current_pixel
        if hits > min:
            print("exists")
            return True
        if(validate_color_at_coords(coordinates, brightness)): hits += 1
        elif hits > 0: hits += -1
        counter += 1
    print("not open")
    return False

def post_process_name(name):
    ##############################################
    # TODO: hide this from the light of day      #
    ##############################################
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

# https://stackoverflow.com/questions/3229419/how-to-pretty-print-nested-dictionaries
def pretty(d, indent=0):
   for key, value in d.items():
      print('\t' * indent + str(key))
      if isinstance(value, dict):
         pretty(value, indent+1)
      else:
         print('\t' * (indent+1) + str(value))

############################
# Start                  #
############################

main()