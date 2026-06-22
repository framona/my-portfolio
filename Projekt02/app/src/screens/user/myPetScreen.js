import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, Image, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function MyPetsScreen({ navigation }) {
  const [pets, setPets] = useState([]);
  const [loading, setLoading] = useState(true);

  const fetchPets = async () => {
    try {
      setLoading(true);
      const token = await AsyncStorage.getItem('user_token');
      const url = `https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=list_pets&api_token=${token}`;

      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
      });

      const data = await response.json();
      if (data.success) {
        setPets(data.pets);
      } else {
        Alert.alert('Error', data.error || 'Failed to load pets.');
      }
    } catch (err) {
      console.error(err);
      Alert.alert('Error', 'Network error');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const unsubscribe = navigation.addListener('focus', () => {
      fetchPets();
    });
    return unsubscribe;
  }, [navigation]);

  const handleDelete = async (id) => {
    Alert.alert(
      "Delete Pet",
      "Are you sure you want to remove this pet from your profile?",
      [
        { text: "Cancel", style: "cancel" },
        { 
          text: "Delete", 
          style: "destructive", 
          onPress: async () => {
            try {
              const token = await AsyncStorage.getItem('user_token');
              const url = `https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=delete_pet&api_token=${token}`;

              const response = await fetch(url, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ pet_id: id })
              });

              const data = await response.json();
              if (data.success) {
                setPets(pets.filter(pet => pet.id !== id));
                Alert.alert("Success", "Pet record deleted successfully.");
              } else {
                Alert.alert("Error", data.error || "Failed to delete pet.");
              }
            } catch (err) {
              console.error(err);
              Alert.alert("Error", "Network error occurred.");
            }
          }
        }
      ]
    );
  };

  if (loading) {
    return (
      <View style={[styles.container, { justifyContent: 'center', alignItems: 'center' }]}>
        <ActivityIndicator size="large" color="#4CAF50" />
      </View>
    );
  }

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <TouchableOpacity 
        style={styles.addButton} 
        onPress={() => navigation.navigate('AddPet')}
      >
        <Text style={styles.addButtonText}>+ Add New Pet</Text>
      </TouchableOpacity>

      {pets.length === 0 ? (
        <View style={{ alignItems: 'center', marginTop: 50 }}>
          <Text>You have no pets registered.</Text>
        </View>
      ) : (
        pets.map((pet) => (
          <View key={pet.id} style={styles.card}>
            <Image
              source={{ 
                uri: pet.qr_code 
                  ? `https://nak.stud.vts.su.ac.rs/public/${pet.qr_code}?t=${new Date().getTime()}` 
                  : 'https://via.placeholder.com/200' 
              }}
              style={styles.image}
            />
            <Text style={styles.name}>{pet.name}</Text>

            <View style={styles.buttonRow}>
              <TouchableOpacity 
                style={styles.button} 
                onPress={() => navigation.navigate('PetDetail', { petId: pet.id })}
              >
                <Text style={styles.buttonText}>View</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.button, { backgroundColor: '#FFA500' }]} 
                onPress={() => navigation.navigate('EditPet', { petId: pet.id })}
              >
                <Text style={styles.buttonText}>Edit</Text>
              </TouchableOpacity>
              <TouchableOpacity 
                style={[styles.button, { backgroundColor: '#FF4500' }]} 
                onPress={() => handleDelete(pet.id)}
              >
                <Text style={styles.buttonText}>Delete</Text>
              </TouchableOpacity>
            </View>
          </View>
        ))
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { padding: 20, backgroundColor: '#F8FFF8', paddingTop: 60 },
  card: { 
    backgroundColor: '#fff', padding: 16, borderRadius: 12, marginBottom: 20, 
    shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 10, elevation: 5, alignItems: 'center' 
  },
  addButton: { backgroundColor: '#4CAF50', padding: 15, borderRadius: 10, marginBottom: 20, alignItems: 'center' },
  addButtonText: { color: '#fff', fontWeight: 'bold', fontSize: 16 },
  image: { width: 200, height: 200, borderRadius: 10, marginBottom: 10, resizeMode: 'cover' },
  name: { fontSize: 20, fontWeight: 'bold', marginBottom: 6 },
  buttonRow: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 10 },
  button: { flex: 1, backgroundColor: '#4CAF50', padding: 10, borderRadius: 8, marginHorizontal: 4, alignItems: 'center' },
  buttonText: { color: '#fff', fontWeight: '600' },
});