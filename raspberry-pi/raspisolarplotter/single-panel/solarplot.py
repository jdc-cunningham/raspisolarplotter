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

analog_val = mcp.read_adc(0)

multiplier = (reference/steps)

computed_val = (analog_val * multiplier)

computed = str(round(computed_val, 2)) + ' ' + 'V'

send_data_by_url = requests.get("http://raspisolarplotter.com/?action=submit_data&second_key=&id=&date_date=" + cur_date_date + "&date_time=" + cur_date_time + "&day=" + cur_date_day + "&actual_analog=" + str(analog_val) + "&computed=" + computed + "&key=")
send_data_req_result = send_data_by_url.content.decode('utf-8')
