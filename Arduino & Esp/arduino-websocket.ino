#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

#define SS_PIN 10  // Pin for the RFID module
#define RST_PIN 9  // Pin for the RFID module
MFRC522 mfrc522(SS_PIN, RST_PIN);  // Create an MFRC522 instance

LiquidCrystal_I2C lcd(0x27, 16, 2);  // Initialize the LCD with I2C address 0x27, 16 columns and 2 rows

void setup() {
  Serial.begin(9600);  // Start serial communication via USB (connected to PC)
  SPI.begin();         // Initialize SPI communication for MFRC522
  mfrc522.PCD_Init();  // Initialize MFRC522 reader

  lcd.begin(16, 2);    // Initialize the LCD
  lcd.backlight();     // Turn on the backlight (make sure the display is bright)
  lcd.print("Scan RFID tag");  // Display message to scan the RFID tag
}

void loop() {
  // Check if a new card is detected
  if (mfrc522.PICC_IsNewCardPresent()) {
    // If a new card is detected, read its serial number
    if (mfrc522.PICC_ReadCardSerial()) {
      String rfid = "";  // Initialize an empty string for the RFID

      // Loop through the UID bytes and build the string for the RFID
      for (byte i = 0; i < mfrc522.uid.size; i++) {
        rfid += String(mfrc522.uid.uidByte[i], HEX);  // Convert the UID bytes to HEX and append
      }
      rfid.toUpperCase();  // Convert the RFID string to uppercase for consistency

      lcd.clear();  // Clear the LCD screen
      lcd.print("RFID: ");  // Display the label "RFID: "
      lcd.print(rfid);  // Display the scanned RFID UID

      // Send the RFID UID to the serial monitor for further processing (e.g., Node.js WebSocket)
      Serial.println(rfid);  // Send RFID UID to USB serial port

      delay(3000);  // Wait for 3 seconds before resetting the display

      // After 3 seconds, reset the display to ask for another scan
      lcd.clear();  // Clear the LCD screen
      lcd.print("Scan RFID tag");  // Display the prompt to scan a new RFID tag
    }
  }
}
