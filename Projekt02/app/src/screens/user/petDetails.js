import React, { useEffect, useState, useCallback } from 'react';
import { 
    View, Image, Text, StyleSheet, ActivityIndicator, 
    Alert, TouchableOpacity, ScrollView, RefreshControl 
} from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function PetDetailScreen({ route, navigation }) {
    const { petId } = route.params;
    const [pet, setPet] = useState(null);
    const [loading, setLoading] = useState(true);
    const [generating, setGenerating] = useState(false);

    const fetchPetDetails = useCallback(async () => {
        try {
            const token = await AsyncStorage.getItem('user_token');
            const response = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=get_pet_details&id=${petId}&api_token=${token}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            const data = await response.json();
            if (data.success) {
                setPet(data.pet);
            } else {
                Alert.alert("Error", data.error || "Failed to load data.");
            }
        } catch (error) {
            Alert.alert("Error", "Network error.");
        } finally {
            setLoading(false);
        }
    }, [petId]);

    useEffect(() => {
        fetchPetDetails();
    }, [fetchPetDetails]);

    const handleQRAction = async () => {
        setGenerating(true);
        try {
            const token = await AsyncStorage.getItem('user_token');
            const response = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_pet.php?action=generate_qr&id=${petId}&api_token=${token}`, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            const data = await response.json();

            if (data.success) {
                Alert.alert("Success", "QR Code generated successfully!");
                setPet(prev => ({ 
                    ...prev, 
                    qr_code: data.qr_url + '?t=' + new Date().getTime() 
                }));
            } else {
                Alert.alert("Error", data.error || "Generation failed.");
            }
        } catch (error) {
            Alert.alert("Error", "Network error during QR generation.");
        } finally {
            setGenerating(false);
        }
    };

    if (loading) return <ActivityIndicator size="large" color="#4CAF50" style={styles.loader} />;
    if (!pet) return <View style={styles.container}><Text>No data found.</Text></View>;

    return (
        <ScrollView 
            style={styles.container}
            contentContainerStyle={styles.scrollContent}
            refreshControl={<RefreshControl refreshing={loading} onRefresh={fetchPetDetails} />}
        >
            <View style={styles.box}>
                <Text style={styles.title}>{pet.name} - Details</Text>
                
                {pet.qr_code ? (
                    <View style={styles.qrContainer}>
                        <Image
                            source={{ uri: `https://nak.stud.vts.su.ac.rs/public/${pet.qr_code}` }}
                            style={styles.qrImage}
                        />
                        <Text style={styles.qrText}>Scan this code to see pet details</Text>
                    </View>
                ) : (
                    <View style={styles.noQrBox}>
                        <Text style={styles.noQrText}>No QR code generated yet.</Text>
                    </View>
                )}

                <View style={styles.infoBox}>
                    <DetailRow label="Species" value={pet.species} />
                    <DetailRow label="Breed" value={pet.breed} />
                    <DetailRow label="Age" value={`${pet.age} years`} />
                    <DetailRow label="Vet ID" value={pet.vet_id} />
                    <DetailRow label="Registered" value={pet.created_at} />
                </View>

                <View style={styles.buttonContainer}>
                    <TouchableOpacity 
                        style={[styles.button, { backgroundColor: '#FFA500' }]}
                        onPress={() => navigation.navigate('EditPet', { petId: pet.id })}
                    >
                        <Text style={styles.buttonText}>Edit Pet</Text>
                    </TouchableOpacity>

                    <TouchableOpacity 
                        style={[styles.button, { backgroundColor: generating ? '#A5D6A7' : '#4CAF50' }]}
                        onPress={handleQRAction}
                        disabled={generating}
                    >
                        <Text style={styles.buttonText}>
                            {generating ? "Generating..." : (pet.qr_code ? "Refresh QR Code" : "Generate QR Code")}
                        </Text>
                    </TouchableOpacity>
                </View>
            </View>
        </ScrollView>
    );
}

const DetailRow = ({ label, value }) => (
    <View style={styles.row}>
        <Text style={styles.label}>{label}:</Text>
        <Text style={styles.value}>{value || 'N/A'}</Text>
    </View>
);

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#F8FFF8' },
    scrollContent: { padding: 20, paddingTop: 60 },
    loader: { flex: 1, justifyContent: 'center' },
    box: { 
        backgroundColor: '#fff', padding: 20, borderRadius: 14, elevation: 5,
        shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 10 
    },
    qrContainer: { alignItems: 'center', marginVertical: 20 },
    qrImage: { width: 220, height: 220, resizeMode: 'contain', backgroundColor: '#f9f9f9' },
    qrText: { marginTop: 10, color: '#666', fontSize: 12 },
    noQrBox: { padding: 30, alignItems: 'center', backgroundColor: '#f5f5f5', borderRadius: 8, marginVertical: 20 },
    noQrText: { color: '#999' },
    title: { fontSize: 24, fontWeight: 'bold', marginBottom: 20, textAlign: 'center', color: '#2E7D32' },
    infoBox: { marginTop: 10 },
    row: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: '#f0f0f0' },
    label: { fontWeight: 'bold', color: '#666' },
    value: { color: '#333' },
    buttonContainer: { marginTop: 25, gap: 12 },
    button: { padding: 16, borderRadius: 10, alignItems: 'center' },
    buttonText: { color: '#fff', fontWeight: 'bold', fontSize: 16 }
});