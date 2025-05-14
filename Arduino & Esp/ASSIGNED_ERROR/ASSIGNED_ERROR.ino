#include <SPI.h>
#include <MFRC522.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>

#define SS_PIN 10          // RFID SS pin
#define RST_PIN 9          // RFID RST pin
#define BUZZER_PIN 8       // Buzzer pin

MFRC522 mfrc522(SS_PIN, RST_PIN);  // RFID instance
LiquidCrystal_I2C lcd(0x27, 16, 2); // LCD setup

String mode = "Attendance";  // Default mode (displayed on LCD)

void setup() {
  Serial.begin(9600);
  SPI.begin();
  mfrc522.PCD_Init();

  lcd.begin(16, 2);
  lcd.backlight();
  lcd.setCursor(0, 0);
  lcd.print("Scan RFID tag");

  pinMode(BUZZER_PIN, OUTPUT);
}

void loop() {
  // Listen for commands via Serial
  if (Serial.available() > 0) {
    String incoming = Serial.readStringUntil('\n');
    incoming.trim();  // Remove whitespace

    if (incoming.equalsIgnoreCase("attendance")) {
      mode = "Attendance";
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Mode: Attendance");
      delay(1000);
      lcd.clear();
      lcd.print("Scan RFID tag");

    } else if (incoming.equalsIgnoreCase("assign")) {
      mode = "Assignment";
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("Mode: Assignment");
      delay(1000);
      lcd.clear();
      lcd.print("Scan RFID tag");

    } else if (incoming.equalsIgnoreCase("assigned_error")) {
      lcd.clear();
      lcd.setCursor(0, 0);
      lcd.print("RFID Already");
      lcd.setCursor(0, 1);
      lcd.print("Assigned");
      tone(BUZZER_PIN, 500, 300);
      delay(3000);
      lcd.clear();
      lcd.print("Scan RFID tag");
    }
  }

  // Detect and read RFID
  if (mfrc522.PICC_IsNewCardPresent() && mfrc522.PICC_ReadCardSerial()) {
    String rfid = "";

    for (byte i = 0; i < mfrc522.uid.size; i++) {
      rfid += String(mfrc522.uid.uidByte[i], HEX);
    }

    rfid.toUpperCase();  // Consistency

    lcd.clear();
    lcd.setCursor(0, 0);
    lcd.print(mode);
    lcd.setCursor(0, 1);
    lcd.print("RFID: ");
    lcd.print(rfid);

    Serial.println(rfid);  // Send to server

    tone(BUZZER_PIN, 1000, 300);  // Beep
    delay(3000);

    lcd.clear();
    lcd.print("Scan RFID tag");
  }
}
