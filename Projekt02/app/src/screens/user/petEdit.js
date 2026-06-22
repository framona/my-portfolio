import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, ScrollView, Alert, ActivityIndicator } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function EditPetScreen({ route, navigation }) {
    const { petId } = route.params;
    const [loading, setLoading] = useState(true);
    const [updating, setUpdating] = useState(false);

    const [name, setName] = useState('');
    const [species, setSpecies] = useState('');
    const [breed, setBreed] = useState('');
    const [birthYear, setBirthYear] = useState('');

    useEffect(() => {
        const fetchPetDetails = async () => {
            try {
                const token = await AsyncStorage.getItem('user_token');
                const url = `https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=get_pet_details&id=${petId}&api_token=${token}`;
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json'
                    }
                });
                
                const data = await response.json();
                if (data.success) {
                    setName(data.pet.name);
                    setSpecies(data.pet.species);
                    setBreed(data.pet.breed);
                    const calculatedYear = new Date().getFullYear() - (data.pet.age || 0);
                    setBirthYear(calculatedYear.toString());
                } else {
                    Alert.alert("Error", data.error || "Failed to load pet details.");
                }
            } catch (err) {
                console.error(err);
                Alert.alert("Error", "Network error.");
            } finally {
                setLoading(false);
            }
        };

        fetchPetDetails();
    }, [petId]);

    const handleUpdate = async () => {
        setUpdating(true);
        try {
            const token = await AsyncStorage.getItem('user_token');
            const url = `https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=edit_pet&api_token=${token}`;

            const response = await fetch(url, {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    pet_id: petId,
                    pet_name: name,
                    species: species,
                    breed: breed,
                    birth_year: birthYear
                }),
            });

            const data = await response.json();
            if (data.success) {
                Alert.alert("Success", "Pet updated successfully");
                navigation.goBack(); 
            } else {
                Alert.alert("Error", data.error || "Failed to update pet.");
            }
        } catch (err) {
            console.error(err);
            Alert.alert("Error", "Network error.");
        } finally {
            setUpdating(false);
        }
    };

    if (loading) return <View style={styles.center}><ActivityIndicator size="large" color="#4CAF50" /></View>;

    return (
        <ScrollView contentContainerStyle={styles.container}>
            <View style={styles.box}>
                <Text style={styles.title}>Edit Pet</Text>

                <Text style={styles.label}>Pet Name</Text>
                <TextInput style={styles.input} value={name} onChangeText={setName} />

                <Text style={styles.label}>Species</Text>
                <TextInput style={styles.input} value={species} onChangeText={setSpecies} />

                <Text style={styles.label}>Breed</Text>
                <TextInput style={styles.input} value={breed} onChangeText={setBreed} />

                <Text style={styles.label}>Birth Year</Text>
                <TextInput style={styles.input} value={birthYear} onChangeText={setBirthYear} keyboardType="numeric" />

                <TouchableOpacity style={styles.button} onPress={handleUpdate} disabled={updating}>
                    <Text style={styles.buttonText}>{updating ? "Updating..." : "Update Pet"}</Text>
                </TouchableOpacity>
            </View>
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: { flexGrow: 1, padding: 20, backgroundColor: '#F8FFF8', marginTop: 50 },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
    box: { backgroundColor: '#fff', padding: 20, borderRadius: 14, elevation: 5 },
    title: { fontSize: 24, fontWeight: 'bold', marginBottom: 20, textAlign: 'center' },
    label: { fontSize: 16, fontWeight: '600', marginBottom: 5, color: '#444' },
    input: { borderWidth: 1, borderColor: '#C5D8C5', borderRadius: 8, padding: 12, marginBottom: 15, backgroundColor: '#fff' },
    button: { backgroundColor: '#4CAF50', padding: 15, borderRadius: 10, alignItems: 'center', marginTop: 10 },
    buttonText: { color: '#fff', fontWeight: 'bold', fontSize: 16 }
});