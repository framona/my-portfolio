import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, ActivityIndicator, SafeAreaView } from 'react-native';

export default function ForgotPassword({ navigation }) {
    const [email, setEmail] = useState('');
    const [loading, setLoading] = useState(false);

    const handleReset = async () => {
        if (!email) {
            Alert.alert("Error", "Please enter your email!");
            return;
        }

        setLoading(true);
        try {
            const response = await fetch('https://nak.stud.vts.su.ac.rs/public/api/api_auth.php?action=forgot_password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email })
            });

            const data = await response.json();

            if (data.success) {
                Alert.alert("Success", data.message, [{ text: "OK", onPress: () => navigation.goBack() }]);
            } else {
                Alert.alert("Error", data.error || "Something went wrong.");
            }
        } catch (error) {
            Alert.alert("Error", "Connection failed. Please check your server.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.safeArea}>
            <View style={styles.container}>
                <Text style={styles.title}>Reset Password</Text>
                <Text style={styles.subtitle}>Enter your email to receive a reset link.</Text>

                <TextInput
                    style={styles.input}
                    placeholder="you@example.com"
                    value={email}
                    onChangeText={setEmail}
                    keyboardType="email-address"
                    autoCapitalize="none"
                />

                <TouchableOpacity 
                    style={[styles.button, loading && { opacity: 0.7 }]} 
                    onPress={handleReset} 
                    disabled={loading}
                >
                    {loading ? (
                        <ActivityIndicator color="#fff" />
                    ) : (
                        <Text style={styles.buttonText}>Send Link</Text>
                    )}
                </TouchableOpacity>

                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backButton}>
                    <Text style={styles.backText}>Back to Login</Text>
                </TouchableOpacity>
            </View>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    safeArea: { flex: 1, backgroundColor: '#fff' },
    container: { flex: 1, padding: 30, justifyContent: 'center' },
    title: { fontSize: 28, fontWeight: 'bold', color: '#4CAF50', textAlign: 'center' },
    subtitle: { fontSize: 14, color: '#666', textAlign: 'center', marginBottom: 30, marginTop: 10 },
    input: { borderWidth: 1, borderColor: '#ddd', padding: 15, borderRadius: 10, marginBottom: 20, backgroundColor: '#f9f9f9' },
    button: { backgroundColor: '#4CAF50', padding: 18, borderRadius: 10, alignItems: 'center', elevation: 2 },
    buttonText: { color: '#fff', fontWeight: 'bold', fontSize: 16 },
    backButton: { marginTop: 25, alignItems: 'center' },
    backText: { color: '#4CAF50', fontWeight: 'bold', textDecorationLine: 'underline' }
});