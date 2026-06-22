import React, { useState, useEffect } from 'react';
import { StatusBar } from "expo-status-bar";
import {
  View, Text, StyleSheet, ScrollView, Image, TouchableOpacity,
  SafeAreaView, Modal, ActivityIndicator
} from 'react-native';
import { CameraView, useCameraPermissions } from 'expo-camera';
import * as Location from 'expo-location';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function HomeScreen({ navigation }) {
  const [permission, requestPermission] = useCameraPermissions();
  const [scannerVisible, setScannerVisible] = useState(false);
  const [scanned, setScanned] = useState(false);
  const [loading, setLoading] = useState(false);
  const [reminder, setReminder] = useState(null);


  const handleBarCodeScanned = async ({ data }) => {
    if (scanned) return; 
    setScanned(true); 
    setScannerVisible(false); 
    setLoading(true);

    try {
        let location = await Location.getCurrentPositionAsync({ accuracy: Location.Accuracy.Balanced });
        await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_report_lost.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                pet_id: data, 
                action: 'report_found',
                latitude: location.coords.latitude,
                longitude: location.coords.longitude
            })
        });
    } catch (error) {
        console.log(error);
    } finally {
        setTimeout(() => {
            setLoading(false);
            setScanned(false);
        }, 2000);
    }
  };

  const openScanner = async () => {
    const { status: camStatus } = await requestPermission();
    const { status: locStatus } = await Location.requestForegroundPermissionsAsync();
    if (camStatus === 'granted' && locStatus === 'granted') {
      setScanned(false); 
      setScannerVisible(true);
    }
  };

  

  return (
    <SafeAreaView style={{ flex: 1, backgroundColor: '#f8f9fa' }}>
      <StatusBar style="light" backgroundColor="#4CAF50" />
      
      {loading && (
        <View style={styles.loadingOverlay}>
          <ActivityIndicator size="large" color="#4CAF50" />
        </View>
      )}

      <ScrollView style={styles.container}>
        <View style={styles.hero}>
          <Text style={styles.heroTitle}>Keep your pet’s information safe, smart, and always with you.</Text>
          <Text style={styles.heroText}>Register your pet, choose a veterinarian, manage medical records, and more.</Text>
        </View>

        

        <Text style={styles.sectionTitle}>Main Features</Text>

        <View style={styles.card}>
          <Image source={require('../../../assets/img/cute-dog-consultation.jpg')} style={styles.vetImage} />
          <Text style={styles.cardTitle}>🐾 Pet Management</Text>
          <Text style={styles.cardText}>Create and manage detailed profiles for your pets.</Text>
        </View>

        <View style={styles.card}>
          <Image source={require('../../../assets/img/veterinarian-taking-care-pet-dog.jpg')} style={styles.vetImage} />
          <Text style={styles.cardTitle}>📅 Appointments</Text>
          <Text style={styles.cardText}>Schedule, view and manage vet appointments easily.</Text>
        </View>

        <View style={styles.card}>
          <Image source={require('../../../assets/img/cute-little-dog-isolated-yellow.jpg')} style={styles.vetImage} />
          <Text style={styles.cardTitle}>🔐 Security</Text>
          <Text style={styles.cardText}>Secure login, notifications and QR code identification.</Text>
        </View>

        <Text style={styles.sectionTitle}>About the System</Text>
        <Text style={styles.aboutText}>“PetRegistry aims to give every pet owner and veterinarian easy access to all essential pet information.”</Text>
        <Text style={styles.aboutText}>The platform is designed as a secure electronic registry for household pets. Each owner can register their pet, select a veterinarian, and maintain a full digital history.</Text>

        <Text style={styles.sectionTitle}>Our Veterinarians</Text>
        <View style={styles.vetCard}>
          <Image source={require('../../../assets/img/vet.jpg')} style={styles.vetImage} />
          <Text style={styles.vetName}>Dr. Emily Carter</Text>
          <Text style={styles.vetRole}>Canine & Feline Specialist</Text>
        </View>

        <View style={styles.vetCard}>
          <Image source={require('../../../assets/img/female-veterinarian-examining-dog-s-mouth-table-clinic.jpg')} style={styles.vetImage} />
          <Text style={styles.vetName}>Dr. Olivia Martinez</Text>
          <Text style={styles.vetRole}>Nutrition & Chronic Care</Text>
        </View>

        <View style={styles.vetCard}>
          <Image source={require('../../../assets/img/close-up-veterinarian-taking-care-dog.jpg')} style={styles.vetImage} />
          <Text style={styles.vetName}>Dr. Anna Reynolds</Text>
          <Text style={styles.vetRole}>Exotic Animals</Text>
        </View>
      </ScrollView>

      <TouchableOpacity style={styles.sosButton} onPress={openScanner}>
        <Text style={styles.sosButtonIcon}>🔍</Text>
        <Text style={styles.sosButtonText}>SOS Scan</Text>
      </TouchableOpacity>

      <Modal visible={scannerVisible} animationType="slide">
        <View style={styles.scannerContainer}>
          <CameraView
            onBarcodeScanned={scanned ? undefined : handleBarCodeScanned}
            barcodeSettings={{ barcodeTypes: ["qr"] }}
            style={StyleSheet.absoluteFillObject}
          />
          <View style={styles.scannerOverlay}>
            <View style={styles.scannerFrame} />
            <TouchableOpacity style={styles.closeScanner} onPress={() => setScannerVisible(false)}>
              <Text style={styles.closeText}>Cancel</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  container: { backgroundColor: '#f8f9fa' },
  hero: { padding: 24, backgroundColor: '#4CAF50', borderBottomLeftRadius: 20, borderBottomRightRadius: 20 },
  heroTitle: { fontSize: 26, fontWeight: 'bold', color: '#fff', marginTop: 20, marginBottom: 12 },
  heroText: { fontSize: 16, color: '#eaf5ea', marginBottom: 20 },
  primaryButton: { backgroundColor: '#fff', padding: 14, borderRadius: 10, alignItems: 'center' },
  primaryButtonText: { color: '#4CAF50', fontWeight: 'bold', fontSize: 16 },
  sectionTitle: { fontSize: 22, fontWeight: 'bold', margin: 20 },
  card: { alignItems: 'center', backgroundColor: '#fff', marginHorizontal: 20, marginBottom: 15, padding: 16, borderRadius: 12, elevation: 3 },
  cardTitle: { fontSize: 18, fontWeight: 'bold', marginBottom: 6 },
  cardText: { fontSize: 14, color: '#555', textAlign: 'center' },
  aboutText: { textAlign: 'justify', fontSize: 15, color: '#555', marginHorizontal: 20, marginBottom: 20 },
  vetCard: { backgroundColor: '#fff', marginHorizontal: 20, marginBottom: 20, borderRadius: 12, alignItems: 'center', padding: 16, elevation: 2 },
  vetImage: { width: 200, height: 250, borderRadius: 10, marginBottom: 10 },
  vetName: { fontSize: 16, fontWeight: 'bold' },
  vetRole: { fontSize: 14, color: '#777' },
  sosButton: { position: 'absolute', bottom: 30, right: 20, backgroundColor: '#FF5252', paddingVertical: 15, paddingHorizontal: 20, borderRadius: 30, flexDirection: 'row', alignItems: 'center', elevation: 10, borderWidth: 2, borderColor: '#fff' },
  sosButtonIcon: { fontSize: 20, marginRight: 8 },
  sosButtonText: { color: '#fff', fontWeight: 'bold' },
  loadingOverlay: { ...StyleSheet.absoluteFillObject, backgroundColor: 'rgba(255,255,255,0.7)', justifyContent: 'center', alignItems: 'center', zIndex: 9999 },
  scannerContainer: { flex: 1, backgroundColor: '#000' },
  scannerOverlay: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  scannerFrame: { width: 250, height: 250, borderWidth: 2, borderColor: '#4CAF50', borderRadius: 20 },
  closeScanner: { marginTop: 50, backgroundColor: 'rgba(255,255,255,0.2)', padding: 15, borderRadius: 10 },
  closeText: { color: '#fff', fontWeight: 'bold' },
  reminderCard: { backgroundColor: '#FFF9C4', margin: 20, padding: 15, borderRadius: 12, borderLeftWidth: 6, borderLeftColor: '#FBC02D', elevation: 4 },
  reminderHeader: { flexDirection: 'row', alignItems: 'center', marginBottom: 5 },
  reminderBell: { fontSize: 18, marginRight: 8 },
  reminderTitle: { fontSize: 16, fontWeight: 'bold', color: '#827717' },
  reminderText: { fontSize: 14, color: '#333', lineHeight: 20 }
});