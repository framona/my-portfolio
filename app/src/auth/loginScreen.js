import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, Alert, StyleSheet, ScrollView } from 'react-native';

export default function LoginScreen({ navigation, onLogin }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  const handleLogin = () => {
    fetch('https://nak.stud.vts.su.ac.rs/public/api/api_auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          onLogin(data);
        } else {
          Alert.alert('Login Failed', data.error || 'Invalid credentials.');
        }
      })
      .catch(err => {
        console.error(err);
        Alert.alert('Error', 'Network error. Please check your connection.');
      });
  };

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <View style={styles.illustration}>
        <Text style={styles.title}>Welcome back!</Text>
        <Text style={styles.text}>Log in to access your pets, appointments and notifications.</Text>
        <Text style={styles.bullet}>🐾 Manage pet profiles</Text>
        <Text style={styles.bullet}>📅 See your scheduled vet visits</Text>
        <Text style={styles.bullet}>🔐 Secure login with Bearer Token</Text>
      </View>

      <View style={styles.loginBox}>
        <Text style={styles.loginTitle}>Log In</Text>

        <View style={styles.inputContainer}>
          <Text>Email (Username)</Text>
          <TextInput
            style={styles.input}
            placeholder="you@example.com"
            value={email}
            onChangeText={setEmail}
            keyboardType="email-address"
            autoCapitalize="none"
          />
        </View>

        <View style={styles.inputContainer}>
          <Text>Password</Text>
          <TextInput
            style={styles.input}
            placeholder="Your password"
            value={password}
            onChangeText={setPassword}
            secureTextEntry
          />
        </View>

        <TouchableOpacity style={styles.button} onPress={handleLogin}>
          <Text style={styles.buttonText}>Login</Text>
        </TouchableOpacity>

        <TouchableOpacity onPress={() => navigation.navigate('Register')}>
          <Text style={styles.link}>Create a new account</Text>
        </TouchableOpacity>

        <TouchableOpacity onPress={() => navigation.navigate('ForgotPassword')}>
          <Text style={styles.link}>Forgot your password?</Text>
        </TouchableOpacity>
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flexGrow: 1, padding: 20, backgroundColor: '#F8FFF8' },
  illustration: { backgroundColor: '#4CAF50', borderRadius: 18, padding: 20, marginBottom: 20, marginTop: 50 },
  title: { color: '#fff', fontSize: 24, fontWeight: 'bold', marginBottom: 10 },
  text: { color: '#fff', marginBottom: 10 },
  bullet: { color: '#fff', marginBottom: 4 },
  loginBox: { backgroundColor: '#fff', padding: 20, borderRadius: 14, shadowColor: '#000', shadowOpacity: 0.12, shadowRadius: 10, elevation: 5 },
  loginTitle: { fontSize: 22, fontWeight: 'bold', marginBottom: 20, textAlign: 'center' },
  inputContainer: { marginBottom: 12 },
  input: { borderWidth: 1, borderColor: '#C5D8C5', borderRadius: 6, padding: 10, marginTop: 4, backgroundColor: '#F4F8F4' },
  button: { backgroundColor: '#4CAF50', padding: 14, borderRadius: 10, alignItems: 'center', marginTop: 10 },
  buttonText: { color: '#fff', fontWeight: '600', fontSize: 16 },
  link: { color: '#4CAF50', textAlign: 'center', marginTop: 15, textDecorationLine: 'underline' },
});