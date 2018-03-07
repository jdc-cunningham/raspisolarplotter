# raspisolarplotter
A basic raspberry pi solar plotter uploader and display on a remote server

## The website
http://raspisolarplotter.com

This website shows the last 15 measurements from both of the 100mA 5V solar cells taped to my apartment window and also the day's current plot between 8AM and 10PM

## Requirements

### Software 

* remote/client (front end with chart/database) - LAMP stack
  * chart is using c3.js
  * PhantomJs for taking screenshots
* local (Raspberry Pi) Python and code to interface with ADC (I used a tutorial by Adafruit)

### Hardware

You will need:
* Raspberry Pi
* Solar Cell(s)
* Load - Resistors
* ADC - I used an MCP3008
* Breadboard and wires

## The chart - front end interface

I'm using C3.js no particular reason, was just looking for something quick/easy to setup.
I did modify it to have a "slider" if you can call it that. So you can load previous charts. It seems to me to be more efficient to re-render old data points than loading old screenshots.

## The database aspect and "API"

This isn't technically an API, I can write a POST endpoint now to receive the data coupled with some secret key but this was a thing I made just under 5 months ago from the time I created this public repo.

The way the data is sent from Python to the remote server is with a GET request with parameters being passed in (the keys and data such as date, voltage per panel, etc...

## The Raspberry Pi - Python side

Initially I started with a single panel but decided to use two of them to compare/get data points from the left side of my window vs. the right side of my window relative to the path of the sun's arc as it moves across the sky. I don't know what I ever did with that data (which days it was) as currently (for a long time) both panels are positioned next to each other as shown in the photo.

## Public API

I am writing a basic api where you can pull all of the data in a JSON dump with some parameters to limit by month/range.
