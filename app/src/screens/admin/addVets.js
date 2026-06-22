import React, { useState } from 'react';
import {View,Text,TextInput,TouchableOpacity,StyleSheet, ScrollView, Alert, SafeAreaView, ActivityIndicator} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

const emptyForm = {
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    password: ''
};

export default function AddVet({ navigation }) {
    const [form, setForm] = useState(emptyForm);
    const [loading, setLoading] = useState(false);

    const handleAddVet = async () => {
        if (!form.first_name || !form.last_name || !form.email || !form.phone || !form.password) {
            Alert.alert("Error", "All fields are required.");
            return;
        }

        setLoading(true);
        try {
            const token = await AsyncStorage.getItem('user_token');
            
            const response = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_admin.php?action=add_vet&api_token=${token}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(form)
            });
            
            const resData = await response.json();

            if (resData.success) {
                Alert.alert("Success", "Veterinarian added successfully!", [
                    {
                        text: "OK",
                        onPress: () => {
                            setForm(emptyForm);
                            navigation.goBack();
                        }
                    }
                ]);
            } else {
                Alert.alert("Error", resData.error || "Failed to add veterinarian.");
            }
        } catch (err) {
            Alert.alert("Error", "Server connection failed.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView style={styles.safeArea}>
            <ScrollView contentContainerStyle={styles.container}>
                <View style={styles.box}>
                    <Text style={styles.title}>Add New Veterinarian</Text>

                    <Text style={styles.label}>First Name</Text>
                    <TextInput
                        style={styles.input}
                        value={form.first_name}
                        onChangeText={(t) => setForm({ ...form, first_name: t })}
                    />

                    <Text style={styles.label}>Last Name</Text>
                    <TextInput
                        style={styles.input}
                        value={form.last_name}
                        onChangeText={(t) => setForm({ ...form, last_name: t })}
                    />

                    <Text style={styles.label}>Email Address</Text>
                    <TextInput
                        style={styles.input}
                        keyboardType="email-address"
                        autoCapitalize="none"
                        value={form.email}
                        onChangeText={(t) => setForm({ ...form, email: t })}
                    />

                    <Text style={styles.label}>Phone Number</Text>
                    <TextInput
                        style={styles.input}
                        keyboardType="phone-pad"
                        value={form.phone}
                        onChangeText={(t) => setForm({ ...form, phone: t })}
                    />

                    <Text style={styles.label}>Temporary Password</Text>
                    <TextInput
                        style={styles.input}
                        secureTextEntry
                        value={form.password}
                        onChangeText={(t) => setForm({ ...form, password: t })}
                    />

                    <Text style={styles.helperText}>
                        The veterinarian can change this later.
                    </Text>

                    <TouchableOpacity
                        style={[styles.btn, styles.saveBtn]}
                        onPress={handleAddVet}
                        disabled={loading}
                    >
                        {loading ? (
                            <ActivityIndicator color="#fff" />
                        ) : (
                            <Text style={styles.btnText}>Create Veterinarian</Text>
                        )}
                    </TouchableOpacity>

                    <TouchableOpacity
                        style={[styles.btn, styles.backBtn]}
                        onPress={() => navigation.goBack()}
                    >
                        <Text style={styles.backBtnText}>Back</Text>
                    </TouchableOpacity>
                </View>
            </ScrollView>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    safeArea: { flex: 1, backgroundColor: '#F8FFF8' },
    container: { padding: 20 },
    box: {
        backgroundColor: '#fff',
        padding: 20,
        borderRadius: 14,
        elevation: 5,
        shadowColor: '#000',
        shadowOpacity: 0.1,
        shadowRadius: 5
    },
    title: {
        fontSize: 22,
        fontWeight: 'bold',
        marginBottom: 20,
        textAlign: 'center',
        color: '#333'
    },
    label: { fontWeight: 'bold', marginBottom: 5, color: '#555' },
    input: {
        borderWidth: 1,
        borderColor: '#DDD',
        padding: 12,
        borderRadius: 10,
        marginBottom: 15,
        fontSize: 16
    },
    helperText: {
        fontSize: 12,
        color: '#888',
        marginBottom: 20,
        fontStyle: 'italic'
    },
    btn: {
        paddingVertical: 14,
        borderRadius: 10,
        alignItems: 'center',
        marginBottom: 10
    },
    saveBtn: { backgroundColor: '#4CAF50' },
    backBtn: {
        backgroundColor: '#fff',
        borderWidth: 1,
        borderColor: '#CCC'
    },
    btnText: { color: '#fff', fontWeight: 'bold', fontSize: 16 },
    backBtnText: { color: '#666', fontWeight: 'bold' }
});