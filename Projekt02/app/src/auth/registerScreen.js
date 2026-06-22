import React, { useState, useEffect } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, StyleSheet } from 'react-native';
import DropDownPicker from 'react-native-dropdown-picker';
import { KeyboardAwareScrollView } from 'react-native-keyboard-aware-scroll-view';

export default function RegisterScreen({ navigation }) {
  const [email, setEmail] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [petName, setPetName] = useState('');
  const [species, setSpecies] = useState(null);
  const [breed, setBreed] = useState('');
  const [birthYear, setBirthYear] = useState('');
  const [vetId, setVetId] = useState(null);

  const [openSpecies, setOpenSpecies] = useState(false);
  const [openVet, setOpenVet] = useState(false);

  const [speciesItems, setSpeciesItems] = useState([
    { label: 'Dog', value: 'Dog' },
    { label: 'Cat', value: 'Cat' },
    { label: 'Bunny', value: 'Bunny' },
    { label: 'Other', value: 'Other' },
  ]);

  const [vetItems, setVetItems] = useState([]);

  useEffect(() => {
    fetch('https://nak.stud.vts.su.ac.rs/public/api/get_vet.php')
      .then(res => res.json())
      .then(data => {
        setVetItems(data.map(v => ({ label: `${v.first_name} ${v.last_name}`, value: v.id })));
      })
      .catch(err => console.error(err));
  }, []);

  const handleRegister = () => {
    if (!email || !password || !firstName || !lastName || !petName || !species || !vetId) {
        Alert.alert('Error', 'Please fill in all required fields!');
        return;
    }

    if (password !== confirmPassword) {
      Alert.alert('Error', 'Passwords do not match!');
      return;
    }

    fetch('https://nak.stud.vts.su.ac.rs/public/api/api_auth.php?action=register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        email,
        first_name: firstName,
        last_name: lastName,
        phone,
        password,
        confirm_password: confirmPassword,
        pet_name: petName,
        species,
        breed,
        birth_year: birthYear,
        vet_id: vetId
      })
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          Alert.alert("Success", data.message, [{ text: "OK", onPress: () => navigation.goBack() }]);
        } else {
          Alert.alert('Error', data.error || 'Something went wrong.');
        }
      })
      .catch(err => {
        console.error(err);
        Alert.alert('Error', 'Network error. Please try again.');
      });
  };

  return (
    <KeyboardAwareScrollView 
        contentContainerStyle={{ padding: 20 }} 
        enableOnAndroid={true} 
        extraScrollHeight={20}
        nestedScrollEnabled={true}
    >
      <View style={styles.illustration}> 
        <Text style={styles.illustrationTitle}>Welcome to PetRegistry</Text> 
        <Text style={styles.illustrationText}>Register as a pet owner and choose a veterinarian.</Text> 
        <Text style={styles.illustrationBullet}>🐾 Secure pet profiles</Text> 
        <Text style={styles.illustrationBullet}>📅 Vet appointments</Text> 
      </View>

      <View style={styles.registerBox}>
        <Text style={styles.title}>Create Your Account</Text>

        <View style={styles.inputContainer}>
          <Text>📧 Email</Text>
          <TextInput style={styles.input} value={email} onChangeText={setEmail} placeholder="you@example.com" keyboardType="email-address" autoCapitalize="none"/>
        </View>

        <View style={styles.row}>
          <View style={styles.halfInput}>
            <Text>👤 First Name</Text>
            <TextInput style={styles.input} value={firstName} onChangeText={setFirstName} placeholder="John"/>
          </View>
          <View style={styles.halfInput}>
            <Text>👤 Last Name</Text>
            <TextInput style={styles.input} value={lastName} onChangeText={setLastName} placeholder="Doe"/>
          </View>
        </View>

        <View style={styles.inputContainer}>
          <Text>📞 Phone</Text>
          <TextInput style={styles.input} value={phone} onChangeText={setPhone} placeholder="+36 30..." keyboardType="phone-pad"/>
        </View>

        <View style={styles.row}>
          <View style={styles.halfInput}>
            <Text>🔒 Password</Text>
            <TextInput style={styles.input} value={password} onChangeText={setPassword} secureTextEntry/>
          </View>
          <View style={styles.halfInput}>
            <Text>🔒 Confirm</Text>
            <TextInput style={styles.input} value={confirmPassword} onChangeText={setConfirmPassword} secureTextEntry/>
          </View>
        </View>

        <Text style={styles.sectionTitle}>First Pet Information</Text>

        <View style={styles.inputContainer}>
          <Text>🐾 Pet Name</Text>
          <TextInput style={styles.input} value={petName} onChangeText={setPetName} placeholder="Buddy"/>
        </View>

        <View style={[styles.row, { zIndex: 3000 }]}>
          <View style={{ flex: 0.48 }}>
            <Text>Species</Text>
            <DropDownPicker
              listMode="SCROLLVIEW"
              open={openSpecies}
              value={species}
              items={speciesItems}
              setOpen={setOpenSpecies}
              setValue={setSpecies}
              placeholder="Select"
              style={styles.dropdown}
              dropDownContainerStyle={styles.dropdownContainer}
            />
          </View>
          <View style={styles.halfInput}>
            <Text>Breed</Text>
            <TextInput style={styles.input} value={breed} onChangeText={setBreed} placeholder="Golden..."/>
          </View>
        </View>

        <View style={styles.inputContainer}>
          <Text>Birth Year</Text>
          <TextInput style={styles.input} value={birthYear} onChangeText={setBirthYear} placeholder="2020" keyboardType="numeric"/>
        </View>

        <View style={{ zIndex: 2000, marginTop: 10 }}>
          <Text>Choose Veterinarian</Text>
          <DropDownPicker
            listMode="SCROLLVIEW"
            open={openVet}
            value={vetId}
            items={vetItems}
            setOpen={setOpenVet}
            setValue={setVetId}
            placeholder="Select veterinarian"
            style={styles.dropdown}
            dropDownContainerStyle={styles.dropdownContainer}
          />
        </View>

        <TouchableOpacity style={styles.button} onPress={handleRegister}>
          <Text style={styles.buttonText}>Create Account</Text>
        </TouchableOpacity>
      </View>
    </KeyboardAwareScrollView>
  );
}

const styles = StyleSheet.create({
  illustration: { backgroundColor: '#4CAF50', borderRadius: 18, padding: 20, marginBottom: 20, marginTop: 50 }, 
  illustrationTitle: { color: '#fff', fontSize: 22, fontWeight: 'bold', marginBottom: 5 }, 
  illustrationText: { color: '#fff', marginBottom: 5 }, 
  illustrationBullet: { color: '#fff' },
  registerBox: { backgroundColor: '#fff', padding: 20, borderRadius: 14, elevation: 5 },
  title: { fontSize: 20, fontWeight: 'bold', marginBottom: 15 },
  sectionTitle: { fontSize: 17, fontWeight: '600', marginTop: 15, marginBottom: 8 },
  inputContainer: { marginBottom: 10 },
  row: { flexDirection: 'row', justifyContent: 'space-between' },
  halfInput: { flex: 0.48 },
  input: { borderWidth: 1, borderColor: '#C5D8C5', borderRadius: 6, padding: 8, marginTop: 4, backgroundColor: '#F4F8F4' },
  dropdown: { backgroundColor: '#F4F8F4', borderColor: '#C5D8C5' },
  dropdownContainer: { borderColor: '#C5D8C5' },
  button: { backgroundColor: '#4CAF50', padding: 14, borderRadius: 10, alignItems: 'center', marginTop: 30 },
  buttonText: { color: '#fff', fontWeight: 'bold' },
});