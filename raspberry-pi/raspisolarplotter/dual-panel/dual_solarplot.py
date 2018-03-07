# guess this has to be at the very top
from __future__ import division

# from Adafruit tutorial, simpletest.py modified
import os, time
os.environ['TZ'] = 'America/Chicago'
time.tzset()

# Import SPI library (for hardware SPI) and MCP3008 library.
import Adafruit_GPIO.SPI as SPI
import Adafruit_MCP3008

import datetime
import requests


# Software SPI configuration:
CLK  = 4 # 18
MISO = 23
MOSI = 17 # 24
CS   = 25
mcp = Adafruit_MCP3008.MCP3008(clk=CLK, cs=CS, miso=MISO, mosi=MOSI)

# # form request
cur_date = time
cur_date_date = cur_date.strftime('%m-%d-%Y')
cur_date_time = cur_date.strftime('%H:%M %p')
cur_date_day = cur_date.strftime('%A')

key = ''

# get analog value and calculate based on 5V multiplier
multiplier = 0
reference = 5
steps = 1024

def plot_calc_send_req(pin_num):

    global requests, cur_date_date, cur_date_time, cur_date_day, multiplier, reference, steps

    analog_val = mcp.read_adc(pin_num)

    multiplier = (reference/steps)

    computed_val = (analog_val * multiplier)

    computed = str(round(computed_val, 2)) + ' ' + 'V'

    if (pin_num == 0):
        panel_id = 'le_panel'
    elif (pin_num == 1):
        panel_id = 'ri_panel'

    send_data_by_url = requests.get("http://raspisolarplotter.com/?action=submit_data&second_key=&id=&panel_id=" + panel_id + "&date_date=" + cur_date_date + "&date_time=" + cur_date_time + "&day=" + cur_date_day + "&actual_analog=" + str(analog_val) + "&computed=" + computed + "&key=")
    send_data_req_result = send_data_by_url.content.decode('utf-8')

# run loop
for x in range (0, 2):
    plot_calc_send_req(x)
