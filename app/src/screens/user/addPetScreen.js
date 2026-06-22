import React, { useState, useEffect } from 'react';
import { View, Text, Image, TextInput, TouchableOpacity, StyleSheet, Alert } from 'react-native';
import DropDownPicker from 'react-native-dropdown-picker';
import { KeyboardAwareScrollView } from 'react-native-keyboard-aware-scroll-view';
import * as ImagePicker from 'expo-image-picker';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function AddPetScreen({ navigation }) {
    const [name, setName] = useState('');
    const [species, setSpecies] = useState('');
    const [breed, setBreed] = useState('');
    const [age, setAge] = useState('');
    const [vetId, setVetId] = useState(null);
    const [openVet, setOpenVet] = useState(false);
    const [vetItems, setVetItems] = useState([]);
    const [photo, setPhoto] = useState(null);

    useEffect(() => {
        fetch('https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=list_vets')
          .then(res => res.json())
          .then(data => {
            if(data.success) {
                setVetItems(data.vets.map(v => ({ label: `Dr. ${v.first_name} ${v.last_name}`, value: v.id })));
            }
          })
          .catch(err => console.error(err));
      }, []);

    const handleChoosePhoto = async () => { 
        const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
        if (status !== 'granted') {
            Alert.alert("Permission denied", "We need camera roll permissions to make this work!");
            return;
        }
        let result = await ImagePicker.launchImageLibraryAsync({
            mediaTypes: ImagePicker.MediaTypeOptions.Images,
            allowsEditing: true,
            aspect: [1, 1],
            quality: 0.7, 
        });

        if (!result.canceled) {
            setPhoto(result.assets[0]);
        }
    };

    const handleSave = async () => {
        if (!name || !species || !vetId) {
            Alert.alert("Error", "Please fill name, species and select a vet!");
            return;
        }

        try {
            const token = await AsyncStorage.getItem('user_token');
            const formData = new FormData();
            formData.append('name', name);
            formData.append('species', species);
            formData.append('breed', breed);
            formData.append('age', age);
            formData.append('vet_id', vetId);

            if (photo) {
                const uriParts = photo.uri.split('.');
                const fileType = uriParts[uriParts.length - 1];

                formData.append('pet_image', {
                    uri: photo.uri,
                    name: `photo.${fileType}`,
                    type: `image/${fileType}`,
                });
            }

            const url = `https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=add_pet&api_token=${token}`;

            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();
            
            if (data.success) {
                Alert.alert("Success", "Pet added successfully!");
                navigation.goBack(); 
            } else {
                Alert.alert("Error", data.error || "Failed to add pet");
            }
        } catch (err) {
            console.error(err);
            Alert.alert("Error", "Connection failed.");
        }
    };

    return (
        <KeyboardAwareScrollView contentContainerStyle={{ padding: 20 }} enableOnAndroid={true} extraScrollHeight={20}>
            <View style={styles.registerBox}>
                <Text style={styles.label}>Pet Name</Text>
                <TextInput style={styles.input} value={name} onChangeText={setName} placeholder="Rex" />

                <Text style={styles.label}>Species</Text>
                <TextInput style={styles.input} value={species} onChangeText={setSpecies} placeholder="Dog" />

                <Text style={styles.label}>Breed</Text>
                <TextInput style={styles.input} value={breed} onChangeText={setBreed} placeholder="Labrador" />

                <Text style={styles.label}>Age (years)</Text>
                <TextInput style={styles.input} value={age} onChangeText={setAge} keyboardType="numeric" placeholder="3" />

                <Text style={[styles.label, { marginTop: 15 }]}>Select Veterinarian</Text>
                <View style={{ zIndex: 2000 }}>
                    <DropDownPicker
                        listMode="SCROLLVIEW"
                        open={openVet}
                        value={vetId}
                        items={vetItems}
                        setOpen={setOpenVet}
                        setValue={setVetId}
                        setItems={setVetItems}
                        placeholder="Select veterinarian"
                        style={styles.dropdown}
                        dropDownContainerStyle={styles.dropdownContainer}
                    />
                </View>

                <Text style={[styles.label, { marginTop: 15 }]}>Pet Photo</Text>
                <TouchableOpacity style={styles.imageButton} onPress={handleChoosePhoto}>
                    <Text style={styles.imageButtonText}>{photo ? "Change Photo" : "Choose Photo"}</Text>
                </TouchableOpacity>

                {photo && (
                    <Image source={{ uri: photo.uri }} style={styles.previewImage} />
                )}

                <TouchableOpacity style={styles.saveButton} onPress={handleSave}>
                    <Text style={styles.saveButtonText}>Add Pet</Text>
                </TouchableOpacity>
            </View>
        </KeyboardAwareScrollView>
    );
}

const styles = StyleSheet.create({
    registerBox: { 
        backgroundColor: '#fff', 
        padding: 20, 
        borderRadius: 14, 
        elevation: 5,
        marginTop: 20
    },
    label: { fontWeight: 'bold', marginBottom: 5, color: '#333' },
    input: { borderWidth: 1, borderColor: '#C5D8C5', borderRadius: 6, padding: 10, marginBottom: 10, backgroundColor: '#F4F8F4' },
    dropdown: { backgroundColor: '#F4F8F4', borderColor: '#C5D8C5', borderRadius: 6 },
    dropdownContainer: { backgroundColor: '#fff', borderColor: '#C5D8C5' },
    imageButton: {
        backgroundColor: '#E8F5E9',
        borderWidth: 1,
        borderColor: '#4CAF50',
        borderStyle: 'dashed',
        padding: 15,
        borderRadius: 6,
        alignItems: 'center',
    },
    imageButtonText: { color: '#2E7D32', fontWeight: '600' },
    previewImage: { width: '100%', height: 200, borderRadius: 10, marginTop: 10, resizeMode: 'cover' },
    saveButton: { backgroundColor: '#4CAF50', padding: 14, borderRadius: 10, alignItems: 'center', marginTop: 20 },
    saveButtonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
});